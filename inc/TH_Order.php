<?php

if ( !class_exists('TH_Order' ) ) {
    class TH_Order
    {
        // public $thdb = $wpdb;
        public static $attributes = ['booking_id', 'order_id', 'bus_id', 'user_name', 'user_phone', 'user_email', 'bus_start', 'journey_date', 'boarding_point', 'droping_point'];

        public static $table = "wbbm_bus_booking_list";

        function __construct($id=null)
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

                $table .= "<th>". TH_Strings::snake_to_proper_case($a) . "</th>";
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
    
            foreach($query as $result) {
                $order = wc_get_order($result->order_id);

                $name = $order->get_billing_first_name() .' '. $order->get_billing_last_name();//->billing);

                if (!trim($name)) $name = 'Unknown';

                $html .= "<option value='$result->order_id'>$name</option>";
            }

            $html .= "</select>";
              
            echo $html;
        }
    }
}
