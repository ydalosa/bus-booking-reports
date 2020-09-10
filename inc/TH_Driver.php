<?php

if ( !class_exists('TH_Driver' ) ) {
    class TH_Driver
    {
        // public $thdb = $wpdb;
        public static $attributes = ['first_name', 'last_name', 'phone', 'email'];

        public static $table = "th_drivers";

        function __construct($id=null)
        {
            global $wpdb;

            $this->wpdb = $wpdb;
            $this->table = $this->wpdb->prefix . static::$table;

            if ($id) {
                $this->id = $id;

                $driver = $this->wpdb->get_results("SELECT * FROM $this->table WHERE `driver_id`='$id'");

                if (is_array($driver[0]) || is_object($driver[0])) {
                    foreach ($driver[0] as $key => $val) {
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
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone' => $this->phone,
                'email' => $this->email,
              )
            );

            return true;
        }

        public function update()
        {
            $this->wpdb->update(
                $this->table,
                array(
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'phone' => $this->phone,
                    'email' => $this->email,
                ),
                array(
                    'driver_id' => $this->id,
                )
            );

            return true;
        }

        public static function all()
        {
            global $wpdb;

            $table = $wpdb->prefix . static::$table;

            $drivers = $wpdb->get_results("SELECT * FROM $table");

            return $drivers;
        }

        public static function buildTable()
        {
            $drivers = self::all();

            if (!count($drivers)) {
                return '<h3>No drivers exist!</h3><p>Add one below!</p>';
            }

            $table = "<table class='th-table'><thead><tr>";

            foreach (self::$attributes as $a) {
                $table .= "<th>". TH_Strings::snake_to_proper_case($a) . "</th>";
            }

            $table .= "<th></th>"; // Manage button

            $table .= "</tr></thead>";

            $table .= "<tbody>";

            foreach ($drivers as $d) {
                $id = $d->driver_id;
                $first_name = $d->first_name;
                $last_name = $d->last_name;
                $phone = $d->phone;
                $email = $d->email;

                $table .= "<tr data-driver_id='$id'><td>$first_name</td><td>$last_name</td><td>$phone</td><td>$email</td><td><button class='th-btn th-edit-driver' data-driver_id='$id'><span class='dashicons dashicons-admin-generic'></span></button></td></tr>";
            }

            return $table;
        }

        public static function addButton()
        {
            return "<div><button class='th-btn th-add-driver'>Add Driver</button></div>";
        }

        public static function driverSelect()
        {
            $drivers = self::all();

            $select = "<label>Driver:</label><br><select class='th-driver-select'>";
            $select .= "<option disabled selected>Assign a Driver</option>";

            foreach ($drivers as $d) {
                $select .= "<option value='$d->driver_id'>$d->first_name $d->last_name</option>";
            }

            $select .= "</select>";

            return $select;
        }

        public static function getRouteDriver($bus_id, $journey_date)
        {
            global $wpdb;

            $table = $wpdb->prefix . "th_drivers_routes";

            $driver = $wpdb->get_row("SELECT * FROM $table WHERE `bus_id`='$bus_id' AND `journey_date`='$journey_date'");

            return $driver;
        }

        public static function name($id)
        {
            if ($id == 'null') return 'No Driver Assigned';

            $driver = new TH_Driver($id);

            return $driver->first_name . ' ' . $driver->last_name;
        }

        public static function initials($id)
        {
            $driver = new TH_Driver($id);

            return $driver->first_name[0] . $driver->last_name[0];
        }
    }
}