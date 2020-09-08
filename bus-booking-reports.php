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

function th_drivers_table_create()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'th_drivers';
    $sql = "CREATE TABLE $table_name (
        driver_id int(15) NOT NULL AUTO_INCREMENT,
        first_name varchar(55) NOT NULL, 
        last_name varchar(55) NOT NULL, 
        phone varchar(55), 
        email text,
        PRIMARY KEY (driver_id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'th_drivers_table_create');

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('woocommerce/woocommerce.php')) {
    require_once(dirname(__FILE__) . "/inc/th_bus_admin_settings.php");
    require_once(dirname(__FILE__) . "/inc/th_bus_enqueue.php");
    require_once(dirname(__FILE__) . "/inc/TH_Driver.php");
    require_once(dirname(__FILE__) . "/inc/TH_Strings.php");
}

// Require autoloader
require 'vendor/autoload.php';

use Dompdf\Dompdf;

/**
 * Functions
 */
/**
 * @todo Don't generate empty reports
 */
function th_show_calendar()
{
    $month = isset($_GET['bus-month']) ?: date('m');
    $day = isset($_GET['bus-month']) ?: date('d');
    $year = isset($_GET['bus-year']) ?: date('Y');

    if (isset($_GET['download_list']) && isset($_GET['bus_id']) && isset($_GET['date'])) {
        $dateBuild = $_GET['date'];
        th_show_reports($_GET['bus_id'], $_GET['date']);
        die();
        // wp_die();
    }

    $dateBuild = "$year-$month-$day";

    th_build_calendar($month, $year, $dateBuild);
    th_add_modal();
    th_add_calendar_scripts();
}

function th_drivers()
{
    $table = TH_Driver::buildTable();

    $html = '';
    $html .= $table;
    $html .= TH_Driver::addButton();

    echo $html;

    th_add_modal();
    th_add_drivers_scripts();
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
        // TODO: Refunding partials..?
        $sold_seats = $total_seat - $available_seat;

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

                $query = "SELECT DISTINCT order_id, COUNT(order_id) as tickets_purchased FROM $table_name WHERE boarding_point='$p->boarding_point' AND journey_date='$dateBuild' AND bus_id='$id' AND (status=2 OR status=1) GROUP BY order_id";

                $order_ids = $wpdb->get_results($query);

                $name_build = '';
                if ($order_ids) {
                    $name_build .= '<div class="th-attendee-list" data-bus_id="' . $id . '">';
                    foreach ($order_ids as $o_id) {
                        $order = wc_get_order($o_id->order_id);
                        if ($order->get_status() !== 'completed') continue;


                        $name = "<div data-oid='$o_id->order_id'>";

                        $name .= $order->get_billing_first_name();
                        $name .= ' ' . $order->get_billing_last_name();

                        $name_build .= $name . ', ' . $o_id->tickets_purchased . ' Ticket(s)</div>';
                    }
                    $name_build .= '</div>';
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
    $order_ids = $wpdb->get_results("SELECT order_id FROM $table_name WHERE bus_id=$bus_id AND journey_date='$date' AND (status=2 OR status=1)");

    $completed_bookings = [];

    if ($order_ids) {
        foreach ($order_ids as $id) {
            if (th_verify_order_status($id->order_id)) {
                array_push($completed_bookings, $id->order_id);
            }
        }
    }

    return count($completed_bookings);
}

function th_verify_order_status($id)
{
    $order = wc_get_order($id);

    return $order->get_status() === 'completed';
}

function th_generate_report_html($arr, $title)
{
    $filename = $title . " " . date("m-d-Y", strtotime($_GET['date'])) . ".pdf";
    print th_download_report($arr, $filename, $title);
}

function th_generate_report($id, $dateBuild) {
    global $wpdb;

    $post = get_post($id);

    $title = $post->post_title; // the_title('', '', false);

    $pickups = th_bus_get_pickup_number($id, $dateBuild);

    $results = [
      ['ID', 'Pickup Time', 'Boarding Point', 'Dropping Point', 'Tickets', 'First Name', 'Last Name', 'Phone', 'Email'],
    ];

    foreach ($pickups as $p) {
      global $wpdb;
      $table_name = $wpdb->prefix . "wbbm_bus_booking_list";
      
      $query = "SELECT DISTINCT order_id, COUNT(order_id) as tickets_purchased FROM $table_name WHERE boarding_point='$p->boarding_point' AND journey_date='$dateBuild' AND bus_id='$id' AND (status=2 OR status=1) GROUP BY order_id ORDER BY bus_start ASC";

      $order_ids = $wpdb->get_results($query);  

      foreach ($order_ids as $o_id) {
         $query = "SELECT droping_point, bus_start, journey_date FROM $table_name WHERE order_id='$o_id->order_id' AND journey_date='$dateBuild'";

        $droppingPointBuild = $wpdb->get_results($query);//->droping_point;
        $droppingPoint = $droppingPointBuild[0]->droping_point;
        $busStart = $droppingPointBuild[0]->bus_start;
        $journeyDate = $droppingPointBuild[0]->journey_date;

        $order = wc_get_order($o_id->order_id);
        if ($order->get_status() !== 'completed') continue;

        $name = "<div data-id='$o_id->order_id'>";
        $name .= $order->get_billing_first_name();
        $name .= ' ' . $order->get_billing_last_name();
        $order->get_billing_email();

        $results[] = [
          'ID' => $o_id->order_id,
          'Pickup Time' => $busStart,
          'Boarding Point' => $p->boarding_point,
          'Dropping Point' => $droppingPoint,
          'Tickets' => $o_id->tickets_purchased,
          'First Name' => $order->get_billing_first_name(),
          'Last Name' => $order->get_billing_last_name(),
          'Phone' => $order->get_billing_phone(),
          'Email' => $order->get_billing_email(),
        ];
      }
    }

    th_generate_report_html($results, $title);
}

function th_show_reports() {
    if (isset($_GET['download_list']) && isset($_GET['bus_id']) && isset($_GET['date'])) {
        ob_clean();
        th_generate_report($_GET['bus_id'], $_GET['date']);
    }
    // wp_die();
}

function th_download_report(array &$array, $filename, $title)
{
    if (count($array) == 0) {
        return null;
    }

    $dompdf = new Dompdf();
  
    $html = '';

    $style = "<style>
    table, th, td {
      border: 1px solid black;
      border-collapse: collapse;
    }
    .inline {
        display: inline-block;
        height: 80px;
    }
    .shaded {
        background: #e7e7e7;
    }
    </style>
    ";

    $html .= $style;
    // fwrite($fh, $style);
    $logo = th_base64('https://flyawayshuttle.com/wp-content/uploads/2020/07/Logo071720-1.png');

    $header = "<div class='inline' style='width:75%;'><img style='width:33%;' src='$logo'></div>";
    // fwrite($fh, $header);
    $html .= $header;

    $box = "<div class='inline' style='float: right;'>";
    $box .= "<div>" . date("D", strtotime($_GET['date'])) . " $title</div>";
    $box .= "<div>" . date("m-d-Y", strtotime($_GET['date'])) . "</div>";
    /**
     * @todo fill in driver
     */
    $box .= "<div>" . /* $route->driver; */ 'Gary VanDriel' . "</div>";
    $box .= "</div>";
    // fwrite($fh, $box);
    $html .= $box;

    // fwrite($fh, $header);
    //  fputcsv($df, array_keys(reset($array)));

    $table = "<table style='width: 100%;'>";  

    foreach ($array as $key => $row) {
        if ($key === 0) {
            $table .= "<thead><tr class='shaded'>";
            foreach ($row as $th) {
                $table .= "<th>$th</th>";
            }
            $table .= "</tr></thead><tbody>";
        } else {
            $table .= "<tr>";
            foreach ($row as $th) {
                $table .= "<th>$th</th>";
            }
            $table .= "</tr>";
        }
   }

    $table .= "</tbody></table>";

    // fwrite($fh, $table);
    $html .= $table;

    $dompdf->loadHtml($html);
    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // Output the generated PDF to Browser
    $dompdf->stream($filename);
    
    // fclose($fh);

    ob_end_flush();
}

function th_base64($filepath)
{
    $type = pathinfo($filepath, PATHINFO_EXTENSION);
    // Get the image and convert into string 
    $img = file_get_contents($filepath); 

    // Encode the image string data into base64 
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($img);
    
    return $base64;
}

function th_download_send_headers($filename)
{
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-type: text/html');
    header("Content-Disposition: attachment; filename={$filename}");
    header('Expires: 0');
    header('Pragma: public');
}


function th_add_modal()
{
    $html = '<div id="th_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="th_modal">
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
                    <button type="button" class="btn th-reports--btn mr-2">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </button>
                    <button type="button" class="th-btn th-btn-primary" data-dismiss="modal">Submit</button>
                    <button type="button" class="th-btn th-btn-secondary" data-dismiss="modal">Close</button>
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
                open: function(header = null, html = null) {
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
                const html = $(this).next(`.th-attendee-list[data-bus_id="${id}"]`).html() || '<span>No Passengers</span>';

                $('.th-reports--btn').attr('data-id', id).attr('data-date', date);

                Modal.open(route + ', ' + date, html);
            });

            $('body').on('click', '.th-reports--btn', function() {
                if ($('#th_modal .modal-body').html() === '<span>No Passengers</span>') return;

                $rel = $('.th-reports--btn');
                $id = $rel.attr('data-id');
                $date = $rel.attr('data-date');

                window.open(`${window.location.href}&bus_id=${$id}&download_list=Y&date=${$date}`, '_blank');
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

function th_add_drivers_scripts()
{
    ob_start();
?>
    <script>
        (function($) {
            $('.th-reports--btn').hide();

            /** Modal functions */
            const Modal = {
                open: function(header = null, html = null) {
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

            $('body').on('click', '.th-add-driver', function() {
                const html = `<div>
                    <div class="th-form-group">
                        <label for="firstNameInput">First Name</label>
                        <input type="text" class="form-control" id="firstNameInput" placeholder="First Name" required>
                    </div>
                    <div class="th-form-group">
                        <label for="lastNameInput">Last Name</label>
                        <input type="text" class="form-control" id="lastNameInput" placeholder="Last Name" required>
                    </div>
                    <div class="th-form-group">
                        <label for="phoneNumberInput">Phone Number</label>
                        <input type="text" class="form-control" id="phoneNumberInput" placeholder="Phone Number">
                    </div>
                    <div class="th-form-group">
                        <label for="emailInput">Email</label>
                        <input type="text" class="form-control" id="emailInput" placeholder="Email">
                    </div>
                </div>`;

                Modal.open('New Driver', html);
            });

        })(jQuery);
    </script>
<?php
    $script = ob_get_clean();
    echo $script;
}
?>

<form action="" method="POST" role="form">
    <legend>Form title</legend>

    <div class="form-group">
        <label for="">label</label>
        <input type="text" class="form-control" id="" placeholder="Input field">
    </div>

    

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

