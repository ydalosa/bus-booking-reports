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


add_action( 'init', 'th_bus_booking_reports');
function th_bus_booking_reports() {
    add_menu_page('Bus Manager', 'Bus Manager', 'manage_options', 'th_busreports', 'th_show_reports', 'dashicons-car', 36);
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
    //add_options_page( 'Event Settings', 'Event Settings', 'delete_posts', 'mep_event_settings_page', array($this, 'plugin_page') );
     add_submenu_page('th_busreports', 'Calendar', 'Bus Calendar', 'manage_options', 'bus-calendar-page', 'th_show_calendar');

    //  add_submenu_page('admin.php?page=th_busreports', 'Drivers', 'Drivers', 'manage_options', 'th_busreports_drivers');

     // Custom Reporting
    //  add_submenu_page('wbbm_bus', 'Reports', 'Reports', 'manage_options', 'wbbm_custom_reports', array($this, 'wbbm_show_reports'));
}

add_action( 'admin_menu', 'th_admin_menu' );

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


/**
 * Functions
 */
function th_show_reports() {
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

function th_show_calendar() {
  // Create array containing abbreviations of days of week.
  $daysOfWeek = array('S', 'M', 'T', 'W', 'T', 'F', 'S');

  // What is the first day of the month in question?
  $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);

  // How many days does this month contain?
  $numberDays = date('t', $firstDayOfMonth);

  // Retrieve some information about the first day of the
  // month in question.
  $dateComponents = getdate($firstDayOfMonth);

  // What is the name of the month in question?
  $monthName = $dateComponents['month'];

  // What is the index value (0-6) of the first day of the
  // month in question.
  $dayOfWeek = $dateComponents['wday'];

  // Create the table tag opener and day headers

  $calendar = '<div>';
  $calendar .= "<div><button style='width:100%;' class='today button button-primary'>Today</button></div>";
  $calendar .= "<table class='calendar'>";
  $calendar .= "<caption><span class='month-change' data-direction='previous' style='float:left;'><</span>$monthName $year<span class='month-change' data-direction='next' style='float:right;'>></span></caption>";
  $calendar .= "<tr>";

  // Create the calendar headers

  foreach ($daysOfWeek as $day) {
    $calendar .= "<th class='header'>$day</th>";
  }

  // Create the rest of the calendar

  // Initiate the day counter, starting with the 1st.

  $currentDay = 1;

  $calendar .= "</tr><tr>";

  // The variable $dayOfWeek is used to
  // ensure that the calendar
  // display consists of exactly 7 columns.

  if ($dayOfWeek > 0) {
    $calendar .= "<td colspan='$dayOfWeek'>&nbsp;</td>";
  }

  $month = str_pad($month, 2, "0", STR_PAD_LEFT);

  while ($currentDay <= $numberDays) {

    // Seventh column (Saturday) reached. Start a new row.

    if ($dayOfWeek == 7) {

      $dayOfWeek = 0;
      $calendar .= "</tr><tr>";
    }

    $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);

    $date = "$year-$month-$currentDayRel";

    $class =  ($date === $dateBuild) ? 'day viewing-day' : 'day';
    $calendar .= "<td class='$class' rel='$date'>$currentDay</td>";

    // Increment counters

    $currentDay++;
    $dayOfWeek++;
  }



  // Complete the row of the last week in month, if necessary

  if ($dayOfWeek != 7) {

    $remainingDays = 7 - $dayOfWeek;
    $calendar .= "<td colspan='$remainingDays'>&nbsp;</td>";
  }

  $calendar .= "</tr>";

  $calendar .= "</table>";
  $calendar .= "<div><button class='button day-change' data-direction='previous'>Prev Day</button><button class='button day-change' data-direction='next'>Next Day</button></div>";
  $calendar .= "</div>";

//   addCalendarScripts();
  echo $calendar;
}
