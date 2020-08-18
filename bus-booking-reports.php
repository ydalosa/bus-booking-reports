<?php
/**
 * Plugin Name: Bus Booking Manager Reports Add-on
 * Plugin URI: https://travishowell.net/
 * Description: Plugin to add reports and other functionality to bus booking manager.
 * Version: 1.0
 * Author: Travis Howell
 * Author URI: https://travishowell.com/
 * License: GPLv2 or later
 * Text Domain: th_busreports
 */

if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.
  
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('woocommerce/woocommerce.php')) {
    require_once(dirname(__FILE__)."/inc/th_bus_admin_settings.php");
    require_once(dirname(__FILE__) . "/inc/th_bus_enqueue.php");
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
    $month = isset($_GET['bus-month']) ?: date('m');
    $day = isset($_GET['bus-month']) ?: date('d');
    $year = isset($_GET['bus-year']) ?: date('Y');

    $dateBuild = "$year-$month-$day";

    th_build_calendar($month, $year, $dateBuild);
}

function th_build_calendar($month, $year, $dateBuild) {
  // Create array containing abbreviations of days of week.
  $daysOfWeek = array('SUN', 'MON', 'TUE', 'WED', 'THUR', 'FRI', 'SAT');

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
  $calendar .= "<div><button class='button day-change' data-direction='previous'>Prev Day</button><button class='button day-change' data-direction='next'>Next Day</button></div>";
  $calendar .= "<table class='th-calendar'>";
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

    $class = ($date === $dateBuild) ? 'day viewing-day' : 'day';
    $calendar .= "<td class='$class' rel='$date'>$currentDay</td>";

    th_bus_bookings($dateBuild, TRUE);

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
  $calendar .= "</div>";

//   addCalendarScripts();
  echo $calendar;
}

function th_bus_bookings($dateBuild, $fromDia=FALSE)
{
    $start = $fromDia ? 'DIA' : 'Fort Collins Transit Center';
    $end = $fromDia ? 'Fort Collins Transit Center' : 'DIA';

    $arr = array(
        'post_type' => array('wbbm_bus'),
        'posts_per_page' => -1,
        'order' => 'ASC',
        'orderby' => 'meta_value',
        'meta_key' => 'wbbm_bus_start_time',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'wbbm_bus_bp_stops',
                'value' => $start,
                'compare' => 'LIKE',
            ),
            array(
                'key' => 'wbbm_bus_next_stops',
                'value' => $end,
                'compare' => 'LIKE',
            ),
        )
    );

    $loop = new WP_Query($arr);

    while ($loop->have_posts()) {
        $loop->the_post();
        
        $id = get_the_ID();
        $pickups = th_bus_get_pickup_number($id, $dateBuild);

        if ($pickups) {
            echo '<pre>';
            var_dump($pickups);
            echo '</pre>';
        }
    }
    

}

function th_bus_get_pickup_number($bus_id, $date)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wbbm_bus_booking_list";
  
    $query = "SELECT boarding_point, COUNT(booking_id), bus_start as riders FROM $table_name WHERE bus_id='$bus_id' AND journey_date='$date' AND (status=2 OR status=1) GROUP BY boarding_point, bus_start ORDER BY bus_start ASC";
  
    $riders_by_location = $wpdb->get_results($query);
  
    return $riders_by_location;  
}