<?php
if (!defined('ABSPATH')) { exit; }

class Plans_Admin {
    public function __construct() {
        add_filter('manage_edit-' . Plans_CPT::POST_TYPE . '_columns', [$this, 'columns']);
        add_action('manage_' . Plans_CPT::POST_TYPE . '_posts_custom_column', [$this, 'column_content'], 10, 2);

        // Add JS and nonce to admin list screen for AJAX toggles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_list_assets']);

        // AJAX actions for toggles
        add_action('wp_ajax_plans_toggle_meta', [$this, 'ajax_toggle_meta']);
    }

    public function columns($columns) {
        // Keep title and date, insert custom cols
        $new = [];
        foreach ($columns as $key => $label) {
            if ($key === 'title') {
                $new[$key] = $label;
                $new['plans_price'] = __('Price', 'plans');
                $new['plans_is_annual'] = __('Annual', 'plans');
                $new['plans_is_starred'] = __('Starred', 'plans');
                $new['plans_is_enabled'] = __('Enabled', 'plans');
            } else {
                $new[$key] = $label;
            }
        }
        return $new;
    }

    public function column_content($column, $post_id) {
        switch ($column) {
            case 'plans_price':
                $label = get_post_meta($post_id, Plans_Meta::CUSTOM_PRICE_LABEL, true);
                if ($label !== '') {
                    echo esc_html($label);
                } else {
                    $price = get_post_meta($post_id, Plans_Meta::PRICE, true);
                    echo $price !== '' ? esc_html(number_format_i18n((float)$price, 2)) : '—';
                }
                break;
            case 'plans_is_annual':
                $val = (bool) get_post_meta($post_id, Plans_Meta::IS_ANNUAL, true);
                echo $val ? 'Yes' : 'No';
                break;
            case 'plans_is_starred':
                $this->render_toggle($post_id, Plans_Meta::IS_STARRED);
                break;
            case 'plans_is_enabled':
                $this->render_toggle($post_id, Plans_Meta::IS_ENABLED);
                break;
        }
    }

    private function render_toggle($post_id, $meta_key) {
        $val = (bool) get_post_meta($post_id, $meta_key, true);
        $label = $meta_key === Plans_Meta::IS_STARRED ? __('Starred', 'plans') : __('Enabled', 'plans');
        $status = $val ? 'on' : 'off';
        printf(
            '<button type="button" class="button plans-toggle" data-post="%d" data-key="%s" data-status="%s">%s</button>',
            (int) $post_id,
            esc_attr($meta_key),
            esc_attr($status),
            $val ? $label . ': On' : $label . ': Off'
        );
    }

    public function enqueue_admin_list_assets($hook) {
        // Only on Plans list table
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'edit-' . Plans_CPT::POST_TYPE) {
            return;
        }
        wp_enqueue_script(
            'plans-admin-columns',
            PLANS_PLUGIN_URL . 'assets/js/admin-columns.js',
            ['jquery' /* only admin side, allowed */,], // jQuery use in admin is OK, but we do not depend on it in front-end
            '1.0.0',
            true
        );
        wp_localize_script('plans-admin-columns', 'PlansAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('plans_toggle_meta'),
        ]);
    }

    public function ajax_toggle_meta() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
        check_ajax_referer('plans_toggle_meta', 'nonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $meta_key = isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : '';

        if (!$post_id || !in_array($meta_key, [Plans_Meta::IS_STARRED, Plans_Meta::IS_ENABLED], true)) {
            wp_send_json_error(['message' => 'Bad request'], 400);
        }

        $current = (bool) get_post_meta($post_id, $meta_key, true);
        $new     = $current ? 0 : 1;
        update_post_meta($post_id, $meta_key, $new);

        // Invalidate cached shortcode output
        delete_transient(PLANS_CACHE_KEY);

        wp_send_json_success([
            'new' => (bool) $new,
            'label' => $meta_key === Plans_Meta::IS_STARRED ? 'Starred' : 'Enabled',
        ]);
    }
}
