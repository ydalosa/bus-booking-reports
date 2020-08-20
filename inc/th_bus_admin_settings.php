<?php
if ( !class_exists('TH_Setting_Controls' ) ):
class TH_Setting_Controls
{

    function __construct()
    {
        add_action( 'admin_init', array($this, 'th_bus_booking_reports'));
        add_action( 'admin_menu', array($this, 'th_admin_menu'));
    }

    function th_bus_booking_reports() {
        add_menu_page('Bus Manager', 'Bus Manager', 'manage_options', 'th_busreports', 'th_show_reports', 'dashicons-car', 10);
    }

    // add_action( 'init', 'th_bus_booking_reports_drivers' );
    function th_bus_booking_reports_drivers() {
        $labels = array(
            'name' => __('Bus Reports', 'th_busreports'),
            'singular_name' => __( 'Bus Report' , 'th_busreports' ),
            'add_new' => __( 'New Bus Report' , 'th_busreports' ),
            'add_new_item' => __( 'Add New Bus Report' , 'th_busreports' ),
            'edit_item' => __( 'Edit Bus Report' , 'th_busreports' ),
            'new_item' => __( 'New Bus Report' , 'th_busreports' ),
            'view_item' => __( 'View Bus Report' , 'th_busreports' ),
            'search_items' => __( 'Search Bus Reports' , 'th_busreports' ),
            'not_found' =>  __( 'No Bus Reports Found' , 'th_busreports' ),
            'not_found_in_trash' => __( 'No Bus Reports found in Trash' , 'th_busreports' ),
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

    /* function th_busreports_register_taxonomy() {   
        // books
        $labels = array(
            'name' => __( 'Drivers' , 'th_busreports_drivers' ),
            'singular_name' => __( 'Driver', 'th_busreports_drivers' ),
            'search_items' => __( 'Search Drivers' , 'th_busreports_drivers' ),
            'all_items' => __( 'All Drivers' , 'th_busreports_drivers' ),
            'edit_item' => __( 'Edit Driver' , 'th_busreports_drivers' ),
            'update_item' => __( 'Update Drivers' , 'th_busreports_drivers' ),
            'add_new_item' => __( 'Add New Driver' , 'th_busreports_drivers' ),
            'new_item_name' => __( 'New Driver Name' , 'th_busreports_drivers' ),
            'menu_name' => __( 'Drivers' , 'th_busreports_drivers' ),
        );
        
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'sort' => true,
            'args' => array( 'orderby' => 'term_order' ),
            'rewrite' => array( 'slug' => 'drivers' ),
            'show_admin_column' => true,
            'show_in_rest' => true
    
        );
        
        register_taxonomy( 'th_busreports_driver', array( 'th_busreports_driver' ), $args);
        
    }
    add_action( 'init', 'th_busreports_register_taxonomy' ); */

    // Menu custom TODO MOVE
    function th_admin_menu() {
        add_menu_page('Bus Manager', 'Bus Manager', 'manage_options',   'th_busreports', 'th_show_reports', 'dashicons-car', 36);

        add_submenu_page('th_busreports', 'Calendar', 'Bus Calendar', 'manage_options', 'bus-calendar-page', 'th_show_calendar');

        //  add_submenu_page('admin.php?page=th_busreports', 'Drivers', 'Drivers', 'manage_options', 'th_busreports_drivers');

        // Custom Reporting
        //  add_submenu_page('wbbm_bus', 'Reports', 'Reports', 'manage_options', 'wbbm_custom_reports', array($this, 'wbbm_show_reports'));

        // add_submenu_page('th_busreports', 'Reports', 'Reports', 'manage_options', 'wbbm_custom_reports', 'th_show_reports');

    }

    /**
     * @todo
     * Add driver post type
     */
    function th_add_driver_posts() {
        // page=bus-reports-page
        register_post_type('drivers', array(
            'labels' => array(
                'name' => __('Drivers'),
                'singular_name' => __('Driver'),
            ),
            // 'public' => true,
            // 'has_archive' => true,
            // 'show_in_menu' => ''
        ));
    }
}
endif;

$settings = new TH_Setting_Controls();