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

                $driver = $this->wpdb->get_var("SELECT * FROM $this->table WHERE `driver_id`='$id'");
            
                foreach ($driver as $key => $val) {
                    $this->{$key} = $val;
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
                    'driver_id' => $this->id,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'phone' => $this->phone,
                    'email' => $this->email,
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

            $table .= "</tr></thead>";

            $table .= "<tbody>";

            foreach ($drivers as $d) {
                $first_name = $d['first_name'];
                $last_name = $d['last_name'];
                $phone = $d['phone'];
                $email = $d['email'];

                $table .= "<tr><td>$first_name</td><td>$last_name</td><td>$phone</td><td>$email</td></tr>";
            }

            return $table;
        }

        public static function addButton()
        {
            return "<div><button class='th-btn th-add-driver'>Add Driver</button></div>";
        }
    }
}