<?php
if (!defined('ABSPATH')) { exit; }

class Plans_Shortcode {
    public function __construct() {
        add_shortcode('plans', [$this, 'render']);
        // Register front-end assets (loaded only when shortcode is present).
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * Register CSS/JS used by the shortcode.
     */
    public function register_assets() {
        wp_register_style('plans-front', PLANS_PLUGIN_URL . 'assets/css/plans.css', [], '1.0.1');
        wp_register_script('plans-front', PLANS_PLUGIN_URL . 'assets/js/plans.js', [], '1.0.0', true);
    }

    /**
     * [plans] attributes (all optional):
     * - columns: integer; when set, forces fixed N columns via inline style
     * - currency: price currency symbol, default "$"
     * - decimals: number of decimals for price, default 2
     * - price_suffix_month: default "/mo"
     * - price_suffix_year:  default "/yr"
     * - starred_badge: badge text for starred plans, default "Recommended"
     * - show_switch: 1/0 render tabs switcher, default 1
     */
    public function render($atts = []) {
        $atts = shortcode_atts([
            'columns'            => '',
            'currency'           => '$',
            'decimals'           => 2,
            'price_suffix_month' => '/mo',
            'price_suffix_year'  => '/yr',
            'starred_badge'      => 'Recommended',
            'show_switch'        => 1,
        ], $atts, 'plans');

        // Try to serve cached HTML (enqueue assets anyway so the page has styles/scripts).
        $cached = get_transient(PLANS_CACHE_KEY);
        if ($cached !== false) {
            wp_enqueue_style('plans-front');
            wp_enqueue_script('plans-front');
            return $cached;
        }

        // Query all enabled plans once; filtering by tab is done client-side.
        $plans = get_posts([
            'post_type'      => Plans_CPT::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => Plans_Meta::IS_ENABLED,
                    'value'   => 1,
                    'compare' => '=',
                ],
            ],
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ]);

        $grouped = [
            'monthly' => [],
            'annual'  => [],
        ];

        foreach ($plans as $p) {
            $is_annual = (bool) get_post_meta($p->ID, Plans_Meta::IS_ANNUAL, true);
            $item = [
                'id'       => $p->ID,
                'title'    => get_the_title($p),
                'price'    => get_post_meta($p->ID, Plans_Meta::PRICE, true),
                'label'    => get_post_meta($p->ID, Plans_Meta::CUSTOM_PRICE_LABEL, true),
                'button_t' => get_post_meta($p->ID, Plans_Meta::BUTTON_TEXT, true),
                'button_l' => get_post_meta($p->ID, Plans_Meta::BUTTON_LINK, true),
                'features' => get_post_meta($p->ID, Plans_Meta::FEATURES, true),
                'starred'  => (bool) get_post_meta($p->ID, Plans_Meta::IS_STARRED, true),
            ];
            if (!is_array($item['features'])) {
                $item['features'] = [];
            }
            $grouped[$is_annual ? 'annual' : 'monthly'][] = $item;
        }

        // Build HTML.
        ob_start();
        wp_enqueue_style('plans-front');
        wp_enqueue_script('plans-front');

        $show_switch = (int) $atts['show_switch'] === 1;
        ?>
        <div class="plans-wrap" data-plans>
            <?php if ($show_switch) : ?>
                <div class="plans-tabs" role="tablist" aria-label="Billing period">
                    <button class="plans-tab active" role="tab" aria-selected="true" data-tab="monthly">Monthly</button>
                    <button class="plans-tab" role="tab" aria-selected="false" data-tab="annual">Annual</button>
                </div>
            <?php endif; ?>

            <div class="plans-panels">
                <?php
                echo $this->render_group('monthly', $grouped['monthly'], $atts);
                echo $this->render_group('annual',  $grouped['annual'],  $atts);
                ?>
            </div>
        </div>
        <?php
        $html = trim(ob_get_clean());

        // Cache for 12h. Invalidated on any Plan save/delete/toggle.
        set_transient(PLANS_CACHE_KEY, $html, 12 * HOUR_IN_SECONDS);
        return $html;
    }

    /**
     * Render a single tab panel (monthly or annual).
     *
     * When the "columns" attribute is NOT set, we attach helper classes:
     * - "auto-cols" to indicate CSS should auto-decide,
     * - "cols-N" (N = number of items clamped to 1..4) to keep 3-in-row up to mobile breakpoint
     *   without creating phantom empty tracks.
     */
    private function render_group($slug, $items, $atts) {
        $suffix = $slug === 'annual' ? $atts['price_suffix_year'] : $atts['price_suffix_month'];

        // Decide grid classes and optional inline style (when columns attr is provided).
        $inline_style = '';
        $grid_classes = 'plans-grid';

        if (!empty($atts['columns'])) {
            $cols = max(1, (int) $atts['columns']);
            $inline_style = 'grid-template-columns: repeat(' . $cols . ', 1fr);';
        } else {
            $count = is_array($items) ? count($items) : 0;
            $cols  = max(1, min($count, 4)); // clamp 1..4 so CSS can keep up to 4 columns
            $grid_classes .= ' auto-cols cols-' . $cols;
        }

        ob_start();
        ?>
        <section class="plans-panel <?php echo $slug === 'monthly' ? 'active' : ''; ?>" data-panel="<?php echo esc_attr($slug); ?>">
            <div class="<?php echo esc_attr($grid_classes); ?>" style="<?php echo esc_attr($inline_style); ?>">
                <?php if (empty($items)) : ?>
                    <div class="plans-empty">No plans available.</div>
                <?php else : ?>
                    <?php foreach ($items as $plan) : ?>
                        <article class="plan-card <?php echo $plan['starred'] ? 'is-starred' : ''; ?>">
                            <?php if ($plan['starred']) : ?>
                                <div class="plan-badge"><?php echo esc_html($atts['starred_badge']); ?></div>
                            <?php endif; ?>

                            <h3 class="plan-title"><?php echo esc_html($plan['title']); ?></h3>

                            <div class="plan-price">
                                <?php if ($plan['label'] !== '') : ?>
                                    <span class="plan-price-label"><?php echo esc_html($plan['label']); ?></span>
                                <?php else : ?>
                                    <?php
                                    $price = $plan['price'] !== '' ? (float) $plan['price'] : '';
                                    if ($price !== '') :
                                        ?>
                                        <span class="plan-price-amount">
                                            <span class="plan-price-currency"><?php echo esc_html($atts['currency']); ?></span>
                                            <span class="plan-price-number"><?php echo esc_html(number_format_i18n($price, (int) $atts['decimals'])); ?></span>
                                            <span class="plan-price-suffix"><?php echo esc_html($suffix); ?></span>
                                        </span>
                                    <?php else : ?>
                                        <span class="plan-price-label">Contact Sales</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($plan['features'])) : ?>
                                <ul class="plan-features">
                                    <?php foreach ($plan['features'] as $feat) : ?>
                                        <li><?php echo wp_kses_post($feat); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (!empty($plan['button_l'])) : ?>
                                <p class="plan-cta">
                                    <a class="plan-button" href="<?php echo esc_url($plan['button_l']); ?>" rel="nofollow">
                                        <?php echo esc_html($plan['button_t'] ?: 'Choose Plan'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
