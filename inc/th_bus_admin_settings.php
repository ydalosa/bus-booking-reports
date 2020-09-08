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

    // Menu custom TODO MOVE
    function th_admin_menu() {
        add_menu_page('Bus Manager', 'Bus Manager', 'manage_options',   'th_busreports', 'th_show_reports', 'dashicons-car', 36);

        add_submenu_page('th_busreports', 'Calendar', 'Bus Calendar', 'manage_options', 'bus-calendar-page', 'th_show_calendar');

        add_submenu_page('th_busreports', 'Drivers', 'Drivers', 'manage_options', 'bus-drivers', 'th_drivers');

        //  add_submenu_page('admin.php?page=th_busreports', 'Drivers', 'Drivers', 'manage_options', 'th_busreports_drivers');

        // Custom Reporting
        //  add_submenu_page('wbbm_bus', 'Reports', 'Reports', 'manage_options', 'wbbm_custom_reports', array($this, 'wbbm_show_reports'));

        // add_submenu_page('th_busreports', 'Reports', 'Reports', 'manage_options', 'wbbm_custom_reports', 'th_show_reports');

    }
}
endif;

$settings = new TH_Setting_Controls();