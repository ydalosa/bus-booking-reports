<?php
if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.

// Enqueue dashboard scripts
add_action('admin_enqueue_scripts', 'th_bus_admin_scripts');
function th_bus_admin_scripts() {
    wp_enqueue_style('th-admin-style',plugin_dir_url( __DIR__ ).'css/admin_styles.css',array());
}