<?php
if (!defined('ABSPATH')) { exit; }

class Plans_Cache {
    public function __construct() {
        // Clear cache on various triggers
        add_action('save_post_' . Plans_CPT::POST_TYPE, [$this, 'flush_cache']);
        add_action('deleted_post', [$this, 'maybe_flush_on_delete'], 10, 1);
        add_action('trashed_post', [$this, 'maybe_flush_on_delete'], 10, 1);
        add_action('untrashed_post', [$this, 'maybe_flush_on_delete'], 10, 1);
    }

    public function flush_cache() {
        delete_transient(PLANS_CACHE_KEY);
    }

    public function maybe_flush_on_delete($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === Plans_CPT::POST_TYPE) {
            delete_transient(PLANS_CACHE_KEY);
        }
    }
}