<?php
/*
Plugin Name: Bus Booking Manager Reports Add-on
Plugin URI: https://travishowell.net/
Description: Plugin to add reports and other functionality to bus booking manager.
Version: 1.0
Author: Travis Howell
Author URI: https://travishowell.com/
License: GPLv2 or later
Text Domain: th_busreports
*/

function bus_booking_reports() {
    $labels = array(
        'name' => __('Drivers', 'th_busreports'),
        'singular_name' => __( 'Driver' , 'th_busreports' ),
        'add_new' => __( 'New Driver' , 'th_busreports' ),
        'add_new_item' => __( 'Add New Driver' , 'th_busreports' ),
        'edit_item' => __( 'Edit Driver' , 'th_busreports' ),
        'new_item' => __( 'New Driver' , 'th_busreports' ),
        'view_item' => __( 'View Driver' , 'th_busreports' ),
        'search_items' => __( 'Search Drivers' , 'th_busreports' ),
        'not_found' =>  __( 'No Drivers Found' , 'th_busreports' ),
        'not_found_in_trash' => __( 'No Drivers found in Trash' , 'th_busreports' ),
    );
    $args = array(
        'labels' => $labels,
        'has_archive' => true,
        'public' => true,
        'hierarchical' => false,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'custom-fields',
            'thumbnail',
            'page-attributes'
        ),
        'rewrite'   => array( 'slug' => 'th_busreports' ),
        'show_in_rest' => true
 
    );

    register_post_type( 'th_busreports', $args );
}

add_action( 'init', 'bus_booking_reports' );

function th_busreports_register_taxonomy() {
    // books
    $labels = array(
        'name' => __( 'Genres' , 'th_busreports' ),
        'singular_name' => __( 'Genre', 'th_busreports' ),
        'search_items' => __( 'Search Genres' , 'th_busreports' ),
        'all_items' => __( 'All Genres' , 'th_busreports' ),
        'edit_item' => __( 'Edit Genre' , 'th_busreports' ),
        'update_item' => __( 'Update Genres' , 'th_busreports' ),
        'add_new_item' => __( 'Add New Genre' , 'th_busreports' ),
        'new_item_name' => __( 'New Genre Name' , 'th_busreports' ),
        'menu_name' => __( 'Genres' , 'th_busreports' ),
    );
     
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'sort' => true,
        'args' => array( 'orderby' => 'term_order' ),
        'rewrite' => array( 'slug' => 'genres' ),
        'show_admin_column' => true,
        'show_in_rest' => true
 
    );
     
    register_taxonomy( 'th_busreports_genre', array( 'th_busreports_driver' ), $args);
     
}
add_action( 'init', 'th_busreports_register_taxonomy' );

// Menu custom TODO MOVE
function admin_menu() {
    //add_options_page( 'Event Settings', 'Event Settings', 'delete_posts', 'mep_event_settings_page', array($this, 'plugin_page') );
     add_submenu_page('edit.php?post_type=th_busreports', 'Reports', 'Bus Reports', 'manage_options', 'bus-reports-page', 'wbbm_show_reports');

     // Custom Reporting
    //  add_submenu_page('edit.php?post_type=wbbm_bus', 'Reports', 'Reports', 'manage_options', 'wbbm_custom_reports', array($this, 'wbbm_show_reports'));
}

add_action( 'admin_menu', 'admin_menu' );




/**
 * Functions
 */
function wbbm_show_reports() {
    echo '<h3>hello</h3>';
    // if (mage_get_isset('download_list') && mage_get_isset('bus_id')) {
    //     ob_clean();
    //     generate_report();
    //     die();
    //     wp_die();
    // }

    // echo '<div class="metabox-holder">';
    // echo '<div id="wbbm_reports_container" class="postbox" style="padding: 2rem;">';
    // wbbm_build_reports();
    // echo '</div>';
    // echo '</div>';
}
