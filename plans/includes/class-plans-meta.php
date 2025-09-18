<?php
if (!defined('ABSPATH')) { exit; }

class Plans_Meta {
    // Meta keys required by spec
    const PRICE              = 'price';
    const CUSTOM_PRICE_LABEL = 'custom_price_label';
    const IS_ANNUAL          = 'is_annual';
    const BUTTON_TEXT        = 'button_text';
    const BUTTON_LINK        = 'button_link';
    const FEATURES           = 'features';
    const IS_STARRED         = 'is_starred';
    const IS_ENABLED         = 'is_enabled';

    public function __construct() {
        add_action('admin_notices', [$this, 'admin_notices']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . Plans_CPT::POST_TYPE, [$this, 'save'], 10, 2);

        // Register meta for validation and REST visibility if needed in future
        add_action('init', [$this, 'register_meta']);
    }

    public function register_meta() {
        $meta_args_bool = [
            'type'              => 'boolean',
            'single'            => true,
            'sanitize_callback' => static function ($v) { return (int) (bool) $v; },
            'auth_callback'     => function() { return current_user_can('edit_posts'); },
            'show_in_rest'      => false,
        ];
        $meta_args_string = [
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function() { return current_user_can('edit_posts'); },
            'show_in_rest'      => false,
        ];
        $meta_args_float = [
            'type'              => 'number',
            'single'            => true,
            'sanitize_callback' => static function ($v) { return is_numeric($v) ? (float) $v : ''; },
            'auth_callback'     => function() { return current_user_can('edit_posts'); },
            'show_in_rest'      => false,
        ];

        register_post_meta(Plans_CPT::POST_TYPE, self::PRICE, $meta_args_float);
        register_post_meta(Plans_CPT::POST_TYPE, self::CUSTOM_PRICE_LABEL, $meta_args_string);
        register_post_meta(Plans_CPT::POST_TYPE, self::IS_ANNUAL, $meta_args_bool);
        register_post_meta(Plans_CPT::POST_TYPE, self::BUTTON_TEXT, $meta_args_string);
        register_post_meta(Plans_CPT::POST_TYPE, self::BUTTON_LINK, [
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => function() { return current_user_can('edit_posts'); },
            'show_in_rest'      => false,
        ]);
        register_post_meta(Plans_CPT::POST_TYPE, self::FEATURES, [
            'type'              => 'array',
            'single'            => true,
            'sanitize_callback' => [$this, 'sanitize_features'],
            'auth_callback'     => function() { return current_user_can('edit_posts'); },
            'show_in_rest'      => false,
        ]);
        register_post_meta(Plans_CPT::POST_TYPE, self::IS_STARRED, $meta_args_bool);
        register_post_meta(Plans_CPT::POST_TYPE, self::IS_ENABLED, $meta_args_bool);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'plans_main',
            __('Plan details', 'plans'),
            [$this, 'render'],
            Plans_CPT::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render($post) {
        wp_nonce_field('plans_save_meta', 'plans_meta_nonce');

        $price              = get_post_meta($post->ID, self::PRICE, true);
        $custom_price_label = get_post_meta($post->ID, self::CUSTOM_PRICE_LABEL, true);
        $is_annual          = (bool) get_post_meta($post->ID, self::IS_ANNUAL, true);
        $button_text        = get_post_meta($post->ID, self::BUTTON_TEXT, true);
        $button_link        = get_post_meta($post->ID, self::BUTTON_LINK, true);
        $features           = get_post_meta($post->ID, self::FEATURES, true);
        $is_starred         = (bool) get_post_meta($post->ID, self::IS_STARRED, true);
        $is_enabled         = (bool) get_post_meta($post->ID, self::IS_ENABLED, true);

        if (!is_array($features)) {
            $features = [];
        }
        ?>
        <style>
            .plans-fields-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
            .plans-fields-grid .field { display:flex; flex-direction:column; }
            .plans-features { margin-top:16px; }
            .plans-feature-row { display:flex; gap:8px; margin-bottom:8px; align-items:center; }
            .plans-feature-row input[type="text"] { width:100%; }
            .plans-feature-row button { white-space:nowrap; }
            .plans-flags { display:flex; gap:16px; margin-top:8px; }
        </style>

        <div class="plans-fields-grid">
            <div class="field">
                <label for="plans_price"><strong><?php esc_html_e('Price', 'plans'); ?></strong></label>
                <input type="number" step="0.01" min="0" id="plans_price" name="plans_price" value="<?php echo esc_attr($price); ?>" />
                <p class="description">Numeric price. If "Custom price label" is set, it replaces the price text.</p>
            </div>
            <div class="field">
                <label for="plans_custom_price_label"><strong><?php esc_html_e('Custom price label', 'plans'); ?></strong></label>
                <input type="text" id="plans_custom_price_label" name="plans_custom_price_label" value="<?php echo esc_attr($custom_price_label); ?>" placeholder="Contact Sales" />
            </div>

            <div class="field">
                <label for="plans_button_text"><strong><?php esc_html_e('Button text', 'plans'); ?></strong></label>
                <input type="text" id="plans_button_text" name="plans_button_text" value="<?php echo esc_attr($button_text); ?>" placeholder="Choose Plan" />
            </div>
            <div class="field">
                <label for="plans_button_link"><strong><?php esc_html_e('Button link', 'plans'); ?></strong></label>
                <input type="url" inputmode="url" id="plans_button_link" name="plans_button_link" value="<?php echo esc_url($button_link); ?>" placeholder="https://example.com/checkout" />
            </div>
        </div>

        <div class="plans-flags">
            <label><input type="checkbox" name="plans_is_annual" value="1" <?php checked($is_annual); ?> /> Annual plan</label>
            <label><input type="checkbox" name="plans_is_starred" value="1" <?php checked($is_starred); ?> /> Starred - recommended</label>
            <label><input type="checkbox" name="plans_is_enabled" value="1" <?php checked($is_enabled); ?> /> Enabled - visible in shortcode</label>
        </div>

        <div class="plans-features">
            <label><strong><?php esc_html_e('Features', 'plans'); ?></strong></label>
            <div id="plans-features-wrap">
                <?php
                if (empty($features)) {
                    $features = [''];
                }
                foreach ($features as $idx => $feature) : ?>
                    <div class="plans-feature-row">
                        <input type="text" name="plans_features[]" value="<?php echo esc_attr($feature); ?>" placeholder="Feature text" />
                        <button type="button" class="button plans-remove-feature">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button button-primary" id="plans-add-feature">Add feature</button>
        </div>

        <?php
        // Enqueue small admin script for repeater
        wp_enqueue_script(
            'plans-admin-metabox',
            PLANS_PLUGIN_URL . 'assets/js/admin-metabox.js',
            [],
            '1.0.0',
            true
        );
    }

    public function sanitize_features($value) {
        $out = [];
        if (is_array($value)) {
            foreach ($value as $item) {
                $item = is_string($item) ? trim(wp_kses_post($item)) : '';
                if ($item !== '') {
                    $out[] = $item;
                }
            }
        }
        return $out;
    }

    public function save($post_id, $post) {
        if (!isset($_POST['plans_meta_nonce']) || !wp_verify_nonce($_POST['plans_meta_nonce'], 'plans_save_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // If text provided but link empty/invalid -> show notice after redirect
        if ($button_text !== '' && $button_link === '') {
            add_filter('redirect_post_location', function($loc){
                return add_query_arg('plans_missing_button_link', '1', $loc);
            });
        }

        $price              = isset($_POST['plans_price']) ? (is_numeric($_POST['plans_price']) ? (float) $_POST['plans_price'] : '') : '';
        $custom_price_label = isset($_POST['plans_custom_price_label']) ? sanitize_text_field($_POST['plans_custom_price_label']) : '';
        $is_annual          = isset($_POST['plans_is_annual']) ? 1 : 0;
        $button_text        = isset($_POST['plans_button_text']) ? sanitize_text_field($_POST['plans_button_text']) : '';
        $raw_link           = isset($_POST['plans_button_link']) ? trim((string) $_POST['plans_button_link']) : '';
        $button_link        = $raw_link !== '' ? esc_url_raw($raw_link) : '';
        $features           = isset($_POST['plans_features']) ? $this->sanitize_features($_POST['plans_features']) : [];
        $is_starred         = isset($_POST['plans_is_starred']) ? 1 : 0;
        $is_enabled         = isset($_POST['plans_is_enabled']) ? 1 : 0;

        /** Now: server-side validation -> if text filled but link empty/invalid, show admin notice after redirect */
        if ($button_text !== '' && $button_link === '') {
            add_filter('redirect_post_location', function($loc){
                return add_query_arg('plans_missing_button_link', '1', $loc);
            });
        }

        update_post_meta($post_id, self::PRICE, $price);
        update_post_meta($post_id, self::CUSTOM_PRICE_LABEL, $custom_price_label);
        update_post_meta($post_id, self::IS_ANNUAL, $is_annual);
        update_post_meta($post_id, self::BUTTON_TEXT, $button_text);
        update_post_meta($post_id, self::BUTTON_LINK, $button_link);
        update_post_meta($post_id, self::FEATURES, $features);
        update_post_meta($post_id, self::IS_STARRED, $is_starred);
        update_post_meta($post_id, self::IS_ENABLED, $is_enabled);

        // Invalidate cached shortcode output
        delete_transient(PLANS_CACHE_KEY);
    }

    public function admin_notices() {
        if (!empty($_GET['plans_missing_button_link'])) {
            echo '<div class="notice notice-error"><p><strong>Plans:</strong> "Button text" is set but "Button link" is empty or invalid. Please add a valid URL.</p></div>';
        }
    }

}