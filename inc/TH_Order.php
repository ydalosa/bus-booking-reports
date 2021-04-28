<?php

if (!class_exists('TH_Order')) {
    class TH_Order
    {
        // public $thdb = $wpdb;
        public static $attributes = ['booking_id', 'order_id', 'bus_id', 'user_name', 'user_phone', 'user_email', 'bus_start', 'journey_date', 'boarding_point', 'droping_point'];

        public static $table = "wbbm_bus_booking_list";

        function __construct($id = null)
        {
            global $wpdb;

            $this->wpdb = $wpdb;
            $this->table = $this->wpdb->prefix . static::$table;

            if ($id) {
                $this->id = $id;

                $order = $this->wpdb->get_results("SELECT * FROM $this->table WHERE `booking_id`='$id'");

                if (is_array($order[0]) || is_object($order[0])) {
                    foreach ($order[0] as $key => $val) {
                        $this->{$key} = $val;
                    }
                }
            } else {
                foreach (self::$attributes as $att) {
                    $this->{$att} = '';
                }
            }

            return $this;
        }

        public function save()
        {
            $this->wpdb->insert(
                $this->table,
                array(
                    'order_id' => $this->order_id,
                    'boarding_point' => $this->boarding_point,
                    'droping_point' => $this->droping_point,
                    'user_name' => $this->user_name,
                    'user_email' => $this->user_email,
                    'user_phone' => $this->user_phone,
                    'bus_start' => $this->bus_start,
                    'bus_id' => $this->bus_id,
                    'journey_date' => $this->journey_date,
                    'user_type' => 'Adult/Child',
                    'user_start' => $this->bus_start,
                    'total_adult' => 1,
                    'per_adult_price' => '45',
                    'total_price' => '45',
                    'booking_date' => current_time("Y-m-d h:i:s"),
                    'status' => 2,
                ),
            );

            return true;
        }

        public function update()
        {
            $this->wpdb->update(
                $this->table,
                array(
                    'boarding_point' => $this->boarding_point,
                    'droping_point' => $this->droping_point,
                    'user_name' => $this->user_name,
                    'user_email' => $this->user_email,
                    'user_phone' => $this->user_phone,
                    'bus_start' => $this->bus_start,
                    'bus_id' => $this->bus_id,
                    'journey_date' => $this->journey_date,
                ),
                array(
                    'booking_id' => $this->id,
                )
            );

            return true;
        }

        public static function all()
        {
            global $wpdb;

            $table = $wpdb->prefix . static::$table;

            $orders = $wpdb->get_results("SELECT * FROM $table WHERE booking_id > 113 AND status = 2 ORDER BY booking_id DESC");

            return $orders;
        }

        public static function buildTable()
        {
            $orders = self::all();

            if (!count($orders)) {
                return '<h3>No orders exist!</h3>';
            }

            $table = "<table class='th-table'><thead><tr>";

            foreach (self::$attributes as $a) {
                if ($a === 'booking_id' || $a === 'order_id' || $a === 'bus_id' || $a === 'user_email') continue;

                $table .= "<th>" . TH_Strings::snake_to_proper_case($a) . "</th>";
            }

            $table .= "<th></th>"; // Manage button

            $table .= "</tr></thead>";

            $table .= "<tbody>";

            foreach ($orders as $d) {
                $id = $d->booking_id;
                $order_id = $d->order_id;
                $bus_id = $d->bus_id;
                $boarding_point = $d->boarding_point;
                $droping_point = $d->droping_point;
                $user_name = $d->user_name;
                $user_email = $d->user_email;
                $user_phone = $d->user_phone;
                $bus_start = $d->bus_start;
                $journey_date = $d->journey_date;


                $table .= "<tr data-booking_id='$id'><td>$user_name</td><td>$user_phone</td><td data-bus_id='$bus_id'>$bus_start</td><td>$journey_date</td><td>$boarding_point</td><td>$droping_point</td><td><button class='th-btn th-edit-order' data-booking_id='$id'><span class='dashicons dashicons-admin-generic'></span></button></td></tr>";
            }

            // Add to table extra dropdown resources
            self::gatherDropdownData();

            return $table;
        }

        public static function gatherDropdownData()
        {
            $routes = get_terms(array(
                'taxonomy' => 'wbbm_bus_stops',
                'hide_empty' => false,
            ));

            $html = '<select id="thRoutesDropdown" style="display: none;">';

            foreach ($routes as $r) {
                $name = $r->name;
                $location = $name === 'DIA' ? 'dia' : 'noco';
                $html .= "<option value='$name' data-location='$location'>$name</option>";
            }

            $html .= "</select>";

            // Get bus times
            $arr = array(
                'post_type' => array('wbbm_bus'),
                'posts_per_page' => -1,
                'order' => 'ASC',
                'orderby' => 'meta_value',
                'meta_key' => 'wbbm_bus_start_time',
            );

            $loop = new WP_Query($arr);
            $posts = $loop->posts;

            $html .= "<select id='thTimesDropdown' style='display: none;'>";

            foreach ($posts as $p) {
                $title = explode(' ', $p->post_title);

                $val = $title[0] . ' ' . $title[1];

                $html .= "<option value='$p->ID'>$val</option>";
            }

            $html .= "</select>";

            // $orders = $wpdb->get_results("SELECT * FROM LEFT JOIN $table ON  WHERE booking_id > 113 AND status = 2 ORDER BY booking_id DESC");

            global $wpdb;

            $t = $wpdb->prefix . static::$table;

            /* max( CASE WHEN pm.meta_key = '_billing_first_name' and p.ID = pm.post_id THEN pm.meta_value END ) as first_name,
            max( CASE WHEN pm.meta_key = '_billing_last_name' and p.ID = pm.post_id THEN pm.meta_value END ) as last_name,    */

            $query = $wpdb->get_results("
                SELECT pm.meta_value AS user_id, pm.post_id AS order_id
                FROM {$wpdb->prefix}postmeta AS pm
                LEFT JOIN {$wpdb->prefix}posts AS p
                ON pm.post_id = p.ID
                LEFT JOIN {$t} AS b
                on pm.post_id = b.order_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status = 'wc-completed'
                AND pm.meta_key = '_customer_user'
                AND b.order_id IS NULL
                ORDER BY pm.meta_value ASC, pm.post_id DESC
            ");

            $html .= "<select id='thOrdersDropdown' style='display: none;'><option disabled selected>Choose an Order</option>";

            foreach ($query as $result) {
                $order = wc_get_order($result->order_id);

                $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); //->billing);

                if (!trim($name)) $name = 'Unknown';

                $html .= "<option value='$result->order_id'>$name</option>";
            }

            $html .= "</select>";

            echo $html;
        }

        public static function addCustomCheckoutForm()
        {
            $routes = get_terms(array(
                'taxonomy' => 'wbbm_bus_stops',
                'hide_empty' => false,
            ));

            // $html = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">';
            // $html .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js" integrity="sha384-+YQ4JLhjyBLPDQt//I+STsc9iw4uQqACwlvpslubQzn4u2UU2UFM80nGisd026JF" crossorigin="anonymous"></script>';
            
            $html = '<link rel="stylesheet" href="/wp-content/plugins/woocommerce-gateway-stripe/assets/css/stripe_styles.min.css?ver=4.5.0">';
            $html .= '<script src="/wp-content/plugins/woocommerce-gateway-stripe/assets/js/stripe.min.js?ver=4.5.0"></script>';
            $html .= '<div class="container">';

            $html .= '<div class="card p-4">';
            $html .= '<form id="thCustomOrderForm">';
            $html .= '<div class="form-group"><label for="boardingPointInput">From</label><select class="form-control" id="boardingPointInput">';
        
            $routeDropdown = '';
        
            foreach ($routes as $r) {
                $name = $r->name;
                $location = $name === 'DIA' ? 'dia' : 'noco';
                $routeDropdown .= "<option value='$name' data-location='$location'>$name</option>";
            }
        
            $html .= $routeDropdown;
        
            $html .= "</select>";
        
            $html .= '<label for="dropingPointInput">To</label><select class="form-control" id="dropingPointInput">';
            $html .= $routeDropdown;
            $html .= "</select></div>";
        
        
            // Get bus times
            $arr = array(
                'post_type' => array('wbbm_bus'),
                'posts_per_page' => -1,
                'order' => 'ASC',
                'orderby' => 'meta_value',
                'meta_key' => 'wbbm_bus_start_time',
            );
        
            $loop = new WP_Query($arr);
            $posts = $loop->posts;
        
            $html .= "<div class='form-group'><label for='busStartInput'>Time</label><select class='form-control' id='busStartInput'>";
        
            foreach ($posts as $p) {
                $title = explode(' ', $p->post_title);
        
                $val = $title[0] . ' ' . $title[1];
        
                $html .= "<option value='$p->ID'>$val</option>";
            }
        
            $html .= "</select></div>";

            $html .= "<div class='form-group'><label for='busTicketInput'>Tickets</label><input class='form-control' id='busTicketInput' type='number' min='1'></input></div>";

            $html .= "<div class='form-group'><label for='firstNameInput'>First Name</label><input class='form-control' id='firstNameInput' type='text'></input>";
            $html .= "<label for='lastNameInput'>Last Name</label><input class='form-control' id='lastNameInput' type='text'></input>";
            $html .= "<label for='phoneInput'>Phone Number</label><input class='form-control' id='phoneInput' type='text'></input></div>";

            $html .= "<div class='form-group'><label for='disInput'>Dis</label><input class='form-control' id='disInput' type='checkbox'></input></div>";

            $html .= "</form>";

            $html .= "</div>";

            return $html;
        }

        public static function addCustomCheckoutStyles()
        {
            ob_start();
            ?>
            <style>
                .woocommerce .col-1, .woocommerce .col-2 {max-width:none;}
                .form-control {
                    display: block;
                    width: 100%;
                    height: calc(1.5em + .75rem + 2px);
                    padding: .375rem .75rem;
                    font-size: 1rem;
                    font-weight: 400;
                    line-height: 1.5;
                    color: #495057;
                    background-color: #fff;
                    background-clip: padding-box;
                    border: 1px solid #ced4da;
                    border-radius: .25rem;
                    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
                }
                .form-group {
                    margin-bottom: 1rem;
                }
                label {
                    display: inline-block;
                    margin-bottom: .5rem;
                }
                body:not(.et-tb) #main-content .container, body:not(.et-tb-has-header) #main-content .container {
                    padding-top: 58px;
                }

                .p-4 {
                    padding: 1.5rem!important;
                }

                .card {
                    position: relative;
                    display: -ms-flexbox;
                    display: flex;
                    -ms-flex-direction: column;
                    flex-direction: column;
                    min-width: 0;
                    word-wrap: break-word;
                    background-color: #fff;
                    background-clip: border-box;
                    border: 1px solid rgba(0,0,0,.125);
                    border-radius: .25rem;
                }
                @media (min-width: 992px){
                .container, .container-lg, .container-md, .container-sm {
                    max-width: 960px;
                }}
                @media (min-width: 768px){
                .container, .container-md, .container-sm {
                    max-width: 720px;
                }}
                @media (min-width: 576px){
                .container, .container-sm {
                    max-width: 540px;
                }}
            </style>
            <?php
            $style = ob_clean();

            return $style;
        }

        public static function addCustomCheckoutScripts()
        {
            ob_start();

?>
            <script>
                (function($) {
                    console.log('yaya');

                    const dia = ['DIA'];
                    const noco = ['Centerra Park and Ride', 'Ft Collins Harmony Transfer Center', 'Windsor Park and Ride'];

                    const southbound_routes = ['179', '153', '49', '183', '185'];
                    const northbound_routes = ['181', '187', '189', '191', '193'];

                    $('body').on('change', '#busStartInput', function() {
                        th_updateDisabledOptions();
                    });

                    function th_updateDisabledOptions() {
                        const bus_id = $('#busStartInput').val();

                        if (southbound_routes.indexOf(bus_id) >= 0) {
                            if ($('#boardingPointInput').val() === 'DIA') $('#boardingPointInput').val('Ft Collins Harmony Transfer Center');
                            if ($('#dropingPointInput').val() !== 'DIA') $('#dropingPointInput').val('DIA');

                            $('#boardingPointInput option[data-location="dia"]').attr('disabled', true);
                            $('#boardingPointInput option[data-location="noco"]').attr('disabled', false);

                            $('#dropingPointInput option[data-location="dia"]').attr('disabled', false);
                            $('#dropingPointInput option[data-location="noco"]').attr('disabled', true);
                        } else if (northbound_routes.indexOf(bus_id) >= 0) {
                            if ($('#boardingPointInput').val() !== 'DIA') $('#boardingPointInput').val('DIA');
                            if ($('#dropingPointInput').val() === 'DIA') $('#dropingPointInput').val('Ft Collins Harmony Transfer Center');

                            $('#boardingPointInput option[data-location="dia"]').attr('disabled', false);
                            $('#boardingPointInput option[data-location="noco"]').attr('disabled', true);

                            $('#dropingPointInput option[data-location="dia"]').attr('disabled', true);
                            $('#dropingPointInput option[data-location="noco"]').attr('disabled', false);
                        }
                    }

                    $('body').on('change', '#busStartInput', function() {
                        th_updateDisabledOptions();
                    });

                    th_updateDisabledOptions();
                })(jQuery);
            </script>
<?php
        $ret = ob_get_clean();

        return $ret;
        }
    }
}
