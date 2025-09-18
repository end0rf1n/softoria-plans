<?php
if (!defined('ABSPATH')) { exit; }

class Plans_CPT {
    const POST_TYPE = 'plan';

    public function __construct() {
        add_action('init', [$this, 'register_cpt']);
    }

    public function register_cpt() {
        $labels = [
            'name'               => __('Plans', 'plans'),
            'singular_name'      => __('Plan', 'plans'),
            'menu_name'          => __('Plans', 'plans'),
            'add_new'            => __('Add New', 'plans'),
            'add_new_item'       => __('Add New Plan', 'plans'),
            'edit_item'          => __('Edit Plan', 'plans'),
            'new_item'           => __('New Plan', 'plans'),
            'view_item'          => __('View Plan', 'plans'),
            'search_items'       => __('Search Plans', 'plans'),
            'not_found'          => __('No plans found', 'plans'),
            'not_found_in_trash' => __('No plans found in Trash', 'plans'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false, // No front-end single or archive
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_admin_bar'  => true,
            'exclude_from_search'=> true,
            'publicly_queryable' => false,
            'has_archive'        => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'supports'           => ['title'],
            'menu_icon'          => 'dashicons-list-view',
        ];

        register_post_type(self::POST_TYPE, $args);
    }
}