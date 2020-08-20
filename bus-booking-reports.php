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
    require_once(dirname(__FILE__) . "/inc/th_bus_admin_settings.php");
    require_once(dirname(__FILE__) . "/inc/th_bus_enqueue.php");
}

/**
 * Functions
 */
function th_show_reports()
{
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

function th_show_calendar()
{
    $month = isset($_GET['bus-month']) ?: date('m');
    $day = isset($_GET['bus-month']) ?: date('d');
    $year = isset($_GET['bus-year']) ?: date('Y');

    $dateBuild = "$year-$month-$day";

    th_build_calendar($month, $year, $dateBuild);
    th_add_modal();
    th_add_calendar_scripts();
}

function th_build_calendar($month, $year, $dateBuild)
{
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

    $calendar = '<div>';
    $calendar .= "<div><button style='width:100%;' class='today button button-primary'>Today</button></div>";
    $calendar .= "<table class='th-calendar' border=1 cellspacing=0 cellpadding=0>";
    $calendar .= "<caption><span class='month-change' data-direction='previous' style='float:left;'><</span>$monthName $year<span class='month-change' data-direction='next' style='float:right;'>></span></caption>";
    $calendar .= "<tr>";

    foreach ($daysOfWeek as $day) {
        $calendar .= "<th class='header'>$day</th>";
    }

    $currentDay = 1;

    $calendar .= "</tr><tr>";

    if ($dayOfWeek > 0) {
        $calendar .= "<td colspan='$dayOfWeek'>&nbsp;</td>";
    }

    $month = str_pad($month, 2, "0", STR_PAD_LEFT);

    while ($currentDay <= $numberDays) {
        if ($dayOfWeek == 7) {

            $dayOfWeek = 0;
            $calendar .= "</tr><tr>";
        }

        $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);

        $date = "$year-$month-$currentDayRel";

        $class = ($date === $dateBuild) ? 'th-calenader--current-day th-calenader--day' : 'th-calenader--day';
        $calendar .= "<td><div class='th-calenader--date' rel='$date'><div class='$class'>$currentDay</div>";

        $calendar .= th_bus_bookings($date);
        $calendar .= th_bus_bookings($date, TRUE);

        $calendar .= "</div></td>";

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

    echo $calendar;
}

function th_bus_bookings($dateBuild, $fromDia = FALSE)
{
    $start = $fromDia ? 'DIA' : 'Fort Collins Transit Center';
    $end = $fromDia ? 'Fort Collins Transit Center' : 'DIA';

    $classBuild = $start === 'DIA' ? 'th-calendar--northbound' : 'th-calendar--southbound';

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

    $output = '';

    while ($loop->have_posts()) {
        $loop->the_post();

        $id = get_the_ID();
        $title = the_title('', '', false);

        $boarding = $start;
        $dropping = $end;

        $pickups = th_bus_get_pickup_number($id, $dateBuild);

        $values = get_post_custom(get_the_id());
        $total_seat = $values['wbbm_total_seat'][0];
        $available_seat     = th_available_seat($dateBuild);

        // TODO: Should not count refunded rider
        $sold_seats         = $total_seat - $available_seat;

        $class = $sold_seats > 0 ? $classBuild . ' th-calendar--pill-booked' : $classBuild;

        // List Route Time
        $html = "<div class='th-calendar--pill $class' data-bus_id='$id'>";
        $html .= "<div class='th-calendar--title'>$title</div>";
        // TODO List Driver
        // List # of riders
        $html .= "<div class='th-calendar--riders'>$sold_seats</div>";
        // Generate report button

        $html .= "</div>"; // End .th-calendar--pill

        if ($pickups) {
            foreach ($pickups as $p) {
                global $wpdb;
                $table_name = $wpdb->prefix . "wbbm_bus_booking_list";

                $query = "SELECT DISTINCT order_id, COUNT(order_id) as tickets_purchased FROM $table_name WHERE boarding_point='$p->boarding_point' AND journey_date='$dateBuild' AND (status=2 OR status=1) GROUP BY order_id";

                $order_ids = $wpdb->get_results($query);

                $name_build = '';
                if ($order_ids) {
                    foreach ($order_ids as $o_id) {
                        $name_build .= '<div class="th-attendee-list" data-bus_id="' . $id . '">';

                        $name = "<div data-oid='$o_id->order_id'>";
                        $order = wc_get_order($o_id->order_id);
                        $name .= $order->get_billing_first_name();
                        $name .= ' ' . $order->get_billing_last_name();

                        $name_build .= $name . ', ' . $o_id->tickets_purchased . ' Ticket(s)</div>';
                        $name_build .= '</div>';
                    }
                } else {
                    $name_build = '<div>No Seats Booked</div>';
                }

                $html .= $name_build;
            }
        }


        $output .= $html;
    }

    return $output;
}

function th_bus_get_pickup_number($bus_id, $date)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wbbm_bus_booking_list";

    $query = "SELECT boarding_point, COUNT(booking_id), bus_start as riders FROM $table_name WHERE bus_id='$bus_id' AND journey_date='$date' AND (status=2 OR status=1) GROUP BY boarding_point, bus_start ORDER BY bus_start ASC";

    $riders_by_location = $wpdb->get_results($query);

    return $riders_by_location;
}

function th_available_seat($date)
{
    $values = get_post_custom(get_the_id());
    $total_seat = $values['wbbm_total_seat'][0];
    $sold_seat = th_bus_get_available_seat(get_the_id(), $date);

    return ($total_seat - $sold_seat) > 0 ? ($total_seat - $sold_seat) : 0;
}

function th_bus_get_available_seat($bus_id, $date)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wbbm_bus_booking_list";
    $total_mobile_users = $wpdb->get_var("SELECT COUNT(booking_id) FROM $table_name WHERE bus_id=$bus_id AND journey_date='$date' AND (status=2 OR status=1)");

    return $total_mobile_users;
}

function th_add_modal()
{
    $html = '<div id="th_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="th_modalLabel">
        <div class="modal-underlay"></div>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>test test </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>';

    echo $html;
}

function th_add_calendar_scripts()
{
    ob_start();
?>
    <script>
        (function($) {
            /** Modal functions */
            const Modal = {
                open: function(header=null, html=null) {
                    if (header) {
                        $('.modal-title').html(header);
                    } else {
                        $('.modal-title').html('');
                    }

                    if (html) {
                        $('.modal-body').html(html);
                    } else {
                        $('.modal-body').html('');
                    }
                    
                    $('.modal').addClass('show').show();
                },
                close: function() {
                    $('.modal').removeClass('show').hide();
                },
            }
            $('[data-dismiss="modal"]').click(() => Modal.close());
            $('.modal-underlay').click(function(e) {
                e.preventDefault();

                Modal.close();  
            });

            $('body').on('click', '.th-calendar--pill', function() {
                const id = $(this).attr('data-bus_id');
                const route = $(this).children('.th-calendar--title').text();
                const date = $(this).parents('.th-calenader--date').attr('rel');
                const html = $(`.th-attendee-list[data-bus_id="${id}"]`).html() || '<span>No Passengers</span>';

                Modal.open(route + ' ' + date, html);
            });

            setTimeout(() => {
                console.log('scrolling');
                document.querySelector('.th-calenader--current-day').scrollIntoView();
                window.scrollBy(0, -40);
            }, 250)

        })(jQuery);
    </script>
<?php
    $script = ob_get_clean();
    echo $script;
}
