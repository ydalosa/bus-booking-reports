<?php

/**
 * Plugin Name: Multipurpose Ticket Booking Manager (Bus/Train/Ferry/Boat/Shuttle)
 * Plugin URI: http://mage-people.com
 * Description: A Complete Bus Ticketig System for WordPress & WooCommerce
 * Version: 3.2.5
 * Author: MagePeople Team
 * Author URI: http://www.mage-people.com/
 * Text Domain: bus-booking-manager
 * Domain Path: /languages/
 */

if (!defined('ABSPATH')) {
  die;
} // Cannot access pages directly.

// function to create passenger list table
function wbbm_booking_list_table_create()
{
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();
  $table_name = $wpdb->prefix . 'wbbm_bus_booking_list';
  $sql = "CREATE TABLE $table_name (
    booking_id int(15) NOT NULL AUTO_INCREMENT,
    order_id int(9) NOT NULL,
    bus_id int(9) NOT NULL,
    user_id int(9) NOT NULL,
    boarding_point varchar(55) NOT NULL,
    next_stops text NOT NULL,
    droping_point varchar(55) NOT NULL,
    user_name varchar(55) NOT NULL,
    user_email varchar(55) NOT NULL,
    user_phone varchar(55) NOT NULL,
    user_gender varchar(55) NOT NULL,
    user_address text NOT NULL,
    user_type varchar(55) NOT NULL,
    bus_start varchar(55) NOT NULL,
    user_start varchar(55) NOT NULL,
    total_adult int(9) NOT NULL,
    per_adult_price int(9) NOT NULL,
    total_child int(9) NOT NULL,
    per_child_price int(9) NOT NULL,
    total_price int(9) NOT NULL,
    seat varchar(55) NOT NULL,
    journey_date date DEFAULT '0000-00-00' NOT NULL,
    booking_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    status int(1) NOT NULL,
    PRIMARY KEY  (booking_id)
  ) $charset_collate;";
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
// run the install scripts upon plugin activation
register_activation_hook(__FILE__, 'wbbm_booking_list_table_create');

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('woocommerce/woocommerce.php')) {

  require_once(dirname(__FILE__) . "/inc/class-mage-settings.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_admin_settings.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_cpt.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_tax.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_bus_ticket_meta.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_extra_price.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_shortcode.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_enque.php");
  require_once(dirname(__FILE__) . "/inc/wbbm_upgrade.php");
  //added by sumon
  require_once(dirname(__FILE__) . "/inc/clean/mage_short_code.php");
  require_once(dirname(__FILE__) . "/inc/clean/mage_function.php");
  //--------------

  // Language Load
  add_action('init', 'wbbm_language_load');
  function wbbm_language_load()
  {
    $plugin_dir = basename(dirname(__FILE__)) . "/languages/";
    load_plugin_textdomain('bus-booking-manager', false, $plugin_dir);
  }



  /**
   * Run code only once
   */
  function wbbm_update_databas_once()
  {
    global $wpdb;
    if (get_option('wbbm_update_db_once_06') != 'completed') {
      $table = $wpdb->prefix . "wbbm_bus_booking_list";
      $myCustomer = $wpdb->get_row(sprintf("SELECT * FROM %s LIMIT 1", $table));
      if (!isset($myCustomer->user_type)) {
        $wpdb->query(sprintf("ALTER TABLE %s
            ADD COLUMN user_type varchar(55) NOT NULL AFTER user_address,
            ADD COLUMN total_adult int(9) NOT NULL AFTER user_start,
            ADD COLUMN per_adult_price int(9) NOT NULL AFTER total_adult,
            ADD COLUMN total_child int(9) NOT NULL AFTER per_adult_price,
            ADD COLUMN per_child_price int(9) NOT NULL AFTER total_child,
            ADD COLUMN total_price int(9) NOT NULL AFTER per_child_price", $table));
      }
      update_option('wbbm_update_db_once_06', 'completed');
    }
    if (get_option('wbbm_update_db_once_07') != 'completed') {
      $table = $wpdb->prefix . "wbbm_bus_booking_list";
      $myCustomer = $wpdb->get_row(sprintf("SELECT * FROM %s LIMIT 1", $table));
      if (!isset($myCustomer->next_stops)) {
        $wpdb->query(sprintf("ALTER TABLE %s ADD next_stops text NOT NULL AFTER boarding_point", $table));
      }
      update_option('wbbm_update_db_once_07', 'completed');
    }
  }
  add_action('admin_init', 'wbbm_update_databas_once');


  // AJAX
  // add_action('wp_ajax_generate_report', 'generate_report');
  function generate_report() {
    global $wpdb;

    $id = mage_get_isset('bus_id');
    $bus_start_route = mage_get_isset('bus_start_route') ?: 'Ft Collins Harmony Transfer Center';
    $bus_end_route = mage_get_isset('bus_end_route') ?: 'DIA';
    $j_date = mage_get_isset('j_date');
    $title = mage_get_isset('title');
    $bus_start_route = mage_get_isset('bus_start_route');

    $boarding = $bus_start_route;
    $dropping = $bus_end_route;

    $pickups = wbbm_get_pickup_number($id, wbbm_convert_date_to_php($j_date));

    $results = [
      ['ID', 'Pickup Time', 'Boarding Point', 'Dropping Point', 'Tickets', 'First Name', 'Last Name', 'Phone', 'Email'],
    ];

    // echo "<pre>";
    // var_dump($pickups);
    // echo "</pre>";

    foreach ($pickups as $p) {
      global $wpdb;
      $table_name = $wpdb->prefix . "wbbm_bus_booking_list";

      $query = "SELECT DISTINCT order_id, COUNT(order_id) as tickets_purchased FROM $table_name WHERE boarding_point='$p->boarding_point' AND journey_date='$j_date' AND bus_id='$id' AND (status=2 OR status=1) GROUP BY order_id ORDER BY bus_start ASC";

      $order_ids = $wpdb->get_results($query);
      // echo "<pre>";
      // var_dump($order_ids);
      // echo "</pre>";

      foreach ($order_ids as $o_id) {
         $query = "SELECT droping_point, bus_start, journey_date FROM $table_name WHERE order_id='$o_id->order_id' AND journey_date='$j_date'";

                             $droppingPointBuild = $wpdb->get_results($query);//->droping_point;
                             $droppingPoint = $droppingPointBuild[0]->droping_point;
                             $busStart = $droppingPointBuild[0]->bus_start;
                             $journeyDate = $droppingPointBuild[0]->journey_date;

        $name = "<div data-id='$o_id->order_id'>";
        $order = wc_get_order($o_id->order_id);
        $name .= $order->get_billing_first_name();
        $name .= ' ' . $order->get_billing_last_name();
        $order->get_billing_email();

        $results[] = [
          'ID' => $o_id->order_id,
          // 'Date' => $journeyDate,
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

    // wp_die();

    wbbm_generate_csv($results, $title);

  }





  // Function to get page slug
  function wbbm_get_page_by_slug($slug)
  {
    if ($pages = get_pages())
      foreach ($pages as $page)
        if ($slug === $page->post_name) return $page;
    return false;
  }

  // Cretae pages on plugin activation
  function wbbm_page_create()
  {
    if (!wbbm_get_page_by_slug('bus-search')) {
      $bus_search_page = array(
        'post_type' => 'page',
        'post_name' => 'bus-search',
        'post_title' => 'Bus Search',
        'post_content' => '[bus-search]',
        'post_status' => 'publish',
      );
      wp_insert_post($bus_search_page);
    }
    if (!wbbm_get_page_by_slug('view-ticket')) {
      $view_ticket_page = array(
        'post_type' => 'page',
        'post_name' => 'view-ticket',
        'post_title' => 'View Ticket',
        'post_content' => '[view-ticket]',
        'post_status' => 'publish',
      );
      wp_insert_post($view_ticket_page);
    }
  }
  register_activation_hook(__FILE__, 'wbbm_page_create');

  // Class for Linking with Woocommerce with Bus Pricing
  add_action('plugins_loaded', 'wbbm_load_wc_class');
  function wbbm_load_wc_class()
  {
    if (class_exists('WC_Product_Data_Store_CPT')) {
      class WBBM_Product_Data_Store_CPT extends WC_Product_Data_Store_CPT
      {
        public function read(&$product)
        {

          $product->set_defaults();

          if (!$product->get_id() || !($post_object = get_post($product->get_id())) || !in_array($post_object->post_type, array('wbbm_bus', 'product'))) { // change birds with your post type
            throw new Exception(__('Invalid product.', 'woocommerce'));
          }

          $id = $product->get_id();

          $product->set_props(array(
            'name'              => $post_object->post_title,
            'slug'              => $post_object->post_name,
            'date_created'      => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp($post_object->post_date_gmt) : null,
            'date_modified'     => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp($post_object->post_modified_gmt) : null,
            'status'            => $post_object->post_status,
            'description'       => $post_object->post_content,
            'short_description' => $post_object->post_excerpt,
            'parent_id'         => $post_object->post_parent,
            'menu_order'        => $post_object->menu_order,
            'reviews_allowed'   => 'open' === $post_object->comment_status,
          ));
          $this->read_attributes($product);
          $this->read_downloads($product);
          $this->read_visibility($product);
          $this->read_product_data($product);
          $this->read_extra_data($product);
          $product->set_object_read(true);
        }

        /**
         * Get the product type based on product ID.
         *
         * @since 3.0.0
         * @param int $product_id
         * @return bool|string
         */
        public function get_product_type($product_id)
        {
          $post_type = get_post_type($product_id);
          if ('product_variation' === $post_type) {
            return 'variation';
          } elseif (in_array($post_type, array('wbbm_bus', 'product'))) { // change birds with your post type
            $terms = get_the_terms($product_id, 'product_type');
            return !empty($terms) ? sanitize_title(current($terms)->name) : 'simple';
          } else {
            return false;
          }
        }
      }




      add_filter('woocommerce_data_stores', 'wbbm_woocommerce_data_stores');
      function wbbm_woocommerce_data_stores($stores)
      {
        $stores['product'] = 'WBBM_Product_Data_Store_CPT';
        return $stores;
      }
    } else {

      add_action('admin_notices', 'wc_not_loaded');
    }
  }


  add_action('woocommerce_before_checkout_form', 'wbbm_displays_cart_products_feature_image');
  function wbbm_displays_cart_products_feature_image()
  {
    foreach (WC()->cart->get_cart() as $cart_item) {
      $item = $cart_item['data'];
    }
  }



  add_action('restrict_manage_posts', 'wbbm_filter_post_type_by_taxonomy');
  function wbbm_filter_post_type_by_taxonomy()
  {
    global $typenow;
    $post_type = 'wbbm_bus'; // change to your post type
    $taxonomy  = 'wbbm_bus_cat'; // change to your taxonomy
    if ($typenow == $post_type) {
      $selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
      $info_taxonomy = get_taxonomy($taxonomy);
      wp_dropdown_categories(array(
        'show_option_all' => __("Show All {$info_taxonomy->label}"),
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => $selected,
        'show_count'      => true,
        'hide_empty'      => true,
      ));
    };
  }




  add_filter('parse_query', 'wbbm_convert_id_to_term_in_query');
  function wbbm_convert_id_to_term_in_query($query)
  {
    global $pagenow;
    $post_type = 'wbbm_bus'; // change to your post type
    $taxonomy  = 'wbbm_bus_cat'; // change to your taxonomy
    $q_vars    = &$query->query_vars;

    if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0) {
      $term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
      $q_vars[$taxonomy] = $term->slug;
    }
  }


  function wbbm_load_bus_templates($template)
  {
    global $post;
    if ($post->post_type == "wbbm_bus") {
      $template_name = 'single-bus.php';
      $template_path = 'mage-bus-ticket/';
      $default_path = plugin_dir_path(__FILE__) . 'templates/';
      $template = locate_template(array($template_path . $template_name));
      if (!$template) :
        $template = $default_path . $template_name;
      endif;
      return $template;
    }
    return $template;
  }
  add_filter('single_template', 'wbbm_load_bus_templates');


  add_filter('template_include', 'wbbm_taxonomy_set_template');
  function wbbm_taxonomy_set_template($template)
  {

    if (is_tax('wbbm_bus_cat')) {
      $template = plugin_dir_path(__FILE__) . 'templates/taxonomy-category.php';
    }

    return $template;
  }


  function wbbm_get_bus_ticket_order_metadata($id, $part)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'woocommerce_order_itemmeta';
    $result = $wpdb->get_results("SELECT * FROM $table_name WHERE order_item_id=$id");

    foreach ($result as $page) {
      if (strpos($page->meta_key, '_') !== 0) {
        echo wbbm_get_string_part($page->meta_key, $part) . '<br/>';
      }
    }
  }





  function wbbm_get_seat_type($name)
  {
    global $post;
    $values = get_post_custom($post->ID);
    $seat_name = $name;
    if (array_key_exists($seat_name, $values)) {
      $type_name = $values[$seat_name][0];
    } else {
      $type_name = '';
    }

    $get_terms_default_attributes = array(
      'taxonomy' => 'wbbm_seat_type', //empty string(''), false, 0 don't work, and return
      'hide_empty' => false, //can be 1, '1' too
    );
    $terms = get_terms($get_terms_default_attributes);
    if (!empty($terms) && !is_wp_error($terms)) {
      ob_start();
?>
      <select name="<?php echo $name; ?>" class='seat_type select2'>
        <?php
        foreach ($terms as $term) {
        ?>
          <option value="<?php echo $term->name; ?>" <?php if ($type_name == $term->name) {
                                                        echo "Selected";
                                                      } ?>><?php echo $term->name; ?></option>
        <?php
        }
        ?>
      </select>
    <?php

    }
    $content = ob_get_clean();
    return $content;
  }




  function wbbm_get_bus_route_list($name, $value = '')
  {
    global $post;
    $values     = get_post_custom($post->ID);

    if ($values) {
      $values = $values;
    } else {
      $values = array();
    }




    if (array_key_exists($name, $values)) {
      $seat_name  = $name;
      $type_name  = $values[$seat_name][0];
    } else {
      $type_name = '';
    }
    $terms      = get_terms(array(
      // 'taxonomy' => 'wbbm_bus_route',
      'taxonomy' => 'wbbm_bus_stops',
      'hide_empty' => false,
    ));

    if (!empty($terms) && !is_wp_error($terms)) : ob_start(); ?>

      <select required name="<?php echo $name; ?>" class='seat_type select2'>

        <option value=""><?php _e('Please Select', 'bus-booking-manager'); ?></option>

        <?php foreach ($terms as $term) :
          $wbbm_bs_show = get_term_meta($term->term_id, 'wbbm_bs_show', true);
          if ($wbbm_bs_show) {
            $show = $wbbm_bs_show;
          } else {
            $show = 'yes';
          }
          if ($show == 'yes') {
            $selected = $type_name == $term->name ? 'selected' : '';

            if (!empty($value)) $selected = $term->name == $value ? 'selected' : '';
            printf('<option %s value="%s">%s</option>', $selected, $term->name, $term->name);
          }
        endforeach; ?>

      </select>

    <?php endif;

    return ob_get_clean();
  }


  function wbbm_get_bus_stops_list($name)
  {
    global $post;
    $values = get_post_custom($post->ID);
    $seat_name = $name;
    if (array_key_exists($seat_name, $values)) {
      $type_name = $values[$seat_name][0];
    } else {
      $type_name = '';
    }

    $get_terms_default_attributes = array(
      'taxonomy' => 'wbbm_bus_stops', //empty string(''), false, 0 don't work, and return
      'hide_empty' => false, //can be 1, '1' too
    );
    $terms = get_terms($get_terms_default_attributes);
    if (!empty($terms) && !is_wp_error($terms)) {
      ob_start();
    ?>
      <select name="<?php echo $name; ?>" class='seat_type select2'>
        <option value=""><?php _e('Please Select', 'bus-booking-manager'); ?></option>
        <?php
        foreach ($terms as $term) {
        ?>
          <option value="<?php echo $term->name; ?>" <?php if ($type_name == $term->name) {
                                                        echo "Selected";
                                                      } ?>><?php echo $term->name; ?></option>
        <?php
        }
        ?>
      </select>
    <?php

    }
    $content = ob_get_clean();
    return $content;
  }



  function wbbm_get_next_bus_stops_list($name, $data, $list, $coun)
  {
    global $post;
    $values = get_post_custom($post->ID);
    $nxt_arr = get_post_meta($post->ID, $list, true);
    // print_r($nxt_arr);
    $seat_name = $name;
    $type_name = $nxt_arr[$coun][$data];

    $get_terms_default_attributes = array(
      'taxonomy' => 'wbbm_bus_stops', //empty string(''), false, 0 don't work, and return
      'hide_empty' => false, //can be 1, '1' too
    );
    $terms = get_terms($get_terms_default_attributes);
    if (!empty($terms) && !is_wp_error($terms)) {
      ob_start();
    ?>
      <select name="<?php echo $name; ?>" class='seat_type select2'>
        <option value=""><?php _e('Please Select', 'bus-booking-manager'); ?></option>
        <?php
        foreach ($terms as $term) {
        ?>
          <option value="<?php echo $term->name; ?>" <?php if ($type_name == $term->name) {
                                                        echo "Selected";
                                                      } ?>><?php echo $term->name; ?></option>
        <?php
        }
        ?>
      </select>
    <?php

    }
    $content = ob_get_clean();
    return $content;
  }


  function wbbm_get_bus_price($start, $end, $array)
  {
    foreach ($array as $key => $val) {
      if ($val['wbbm_bus_bp_price_stop'] === $start && $val['wbbm_bus_dp_price_stop'] === $end) {
        return $val['wbbm_bus_price'];
        // return $key;
      }
    }
    return null;
  }


  function wbbm_get_bus_price_child($start, $end, $array)
  {
    foreach ($array as $key => $val) {
      if ($val['wbbm_bus_bp_price_stop'] === $start && $val['wbbm_bus_dp_price_stop'] === $end) {

        return isset($val['wbbm_bus_price_child']);
        // return $key;
      }
    }
    return null;
  }



  function wbbm_get_bus_start_time($start, $array)
  {
    foreach ($array as $key => $val) {
      if ($val['wbbm_bus_bp_stops_name'] === $start) {
        return $val['wbbm_bus_bp_start_time'];
        // return $key;
      }
    }
    return null;
  }



  function wbbm_get_bus_end_time($end, $array)
  {
    foreach ($array as $key => $val) {
      if ($val['wbbm_bus_next_stops_name'] === $end) {
        return $val['wbbm_bus_next_end_time'];
        // return $key;
      }
    }
    return null;
  }

  //add_action('wbbm_search_fields','wbbm_bus_search_fileds');
  function wbbm_bus_search_fileds($start, $end, $date, $r_date)
  {
    ob_start();
    ?>
    <div class="search-fields">

      <div class="fields-li">
        <label>
          <i class="fa fa-map-marker" aria-hidden="true"></i> <?php _e('From', 'bus-booking-manager'); ?>
          <?php echo wbbm_get_bus_route_list('bus_start_route', $start); ?></label>
      </div>

      <div class="fields-li">
        <label>
          <i class="fa fa-map-marker" aria-hidden="true"></i> <?php _e('To:', 'bus-booking-manager'); ?>
          <?php echo wbbm_get_bus_route_list('bus_end_route', $end); ?>
        </label>
      </div>


      <div class="fields-li">
        <label for='j_date'>
          <i class="fa fa-calendar" aria-hidden="true"></i> <?php _e('Date of Journey:', 'bus-booking-manager'); ?>
          <input type="text" id="j_date" name="j_date" value="<?php echo $date; ?>">
        </label>
      </div>


      <div class="fields-li return-date-sec">
        <label for='r_date'>
          <i class="fa fa-calendar" aria-hidden="true"></i> <?php _e('Return Date:', 'bus-booking-manager'); ?>
          <input type="text" id="r_date" name="r_date" value="<?php echo $r_date; ?>">
        </label>
      </div>
      <?php
      if (isset($_GET['bus-r'])) {
        $busr = strip_tags($_GET['bus-r']);
      } else {
        $busr = 'oneway';
      }
      ?>
      <div class="fields-li">
        <div class="search-radio-sec">
          <label for="oneway"><input type="radio" <?php if ($busr == 'oneway') {
                                                    echo 'checked';
                                                  } ?> id='oneway' name="bus-r" value='oneway'> <?php _e('One Way', 'bus-booking-manager'); ?></label>
          <label for="return_date"><input type="radio" <?php if ($busr == 'return') {
                                                          echo 'checked';
                                                        } ?> id='return_date' name="bus-r" value='return'> <?php _e('Return', 'bus-booking-manager'); ?></label>
        </div>
        <button type="submit"><i class='fa fa-search'></i> <?php _e('Search', 'bus-booking-manager'); ?> </button>
      </div>
    </div>
    <script>
      <?php if (isset($_GET['bus-r']) && $_GET['bus-r'] == 'oneway') { ?>
        jQuery('.return-date-sec').hide();
      <?php } elseif (isset($_GET['bus-r']) && $_GET['bus-r'] == 'return') { ?>
        jQuery('.return-date-sec').show();
      <?php } else { ?>
        jQuery('.return-date-sec').hide();
      <?php } ?>
      jQuery('#oneway').on('click', function() {
        jQuery('.return-date-sec').hide();
      });
      jQuery('#return_date').on('click', function() {
        jQuery('.return-date-sec').show();
      });
    </script>
  <?php
    $content = ob_get_clean();
    echo $content;
  }


  function wbbm_get_seat_status($seat, $date, $bus_id, $start)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . "wbbm_bus_booking_list";
    $total_mobile_users = $wpdb->get_results("SELECT status FROM $table_name WHERE seat='$seat' AND journey_date='$date' AND bus_id = $bus_id AND ( boarding_point ='$start' OR next_stops LIKE '%$start%' ) ORDER BY booking_id DESC Limit 1 ");
    return $total_mobile_users;
  }


  function wbbm_get_available_seat($bus_id, $date)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . "wbbm_bus_booking_list";
    $total_mobile_users = $wpdb->get_var("SELECT COUNT(booking_id) FROM $table_name WHERE bus_id=$bus_id AND journey_date='$date' AND (status=2 OR status=1)");
    return $total_mobile_users;
  }

  function wbbm_get_order_meta($item_id, $key)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . "woocommerce_order_itemmeta";
    $sql = 'SELECT meta_value FROM ' . $table_name . ' WHERE order_item_id =' . $item_id . ' AND meta_key="' . $key . '"';
    $results = $wpdb->get_results($sql);
    foreach ($results as $result) {
      $value = $result->meta_value;
    }
    return $value;
  }



  function wbbm_get_order_seat_check($bus_id, $order_id, $user_type, $bus_start, $date)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . "wbbm_bus_booking_list";
    $total_mobile_users = $wpdb->get_var("SELECT COUNT(booking_id) FROM $table_name WHERE bus_id=$bus_id AND order_id = $order_id AND bus_start = '$bus_start' AND user_type = '$user_type' AND journey_date='$date' AND (status = 1 OR status = 2 OR status = 3)");
    return $total_mobile_users;
  }

  // add_action('init','wwbbm_ch');
  function wwbbm_ch()
  {
    global $wpdb, $woocommerce;
    $order      = wc_get_order(117);
    echo '<pre>';
    // print_r($order);
    echo $order->status;
    echo '</pre>';
    if ($order->has_status('pending')) {
      echo 'Yes';
    }
    die();
  }

  // add_action( 'woocommerce_checkout_order_processed', 'wbbm_order_status_before_payment', 10, 3 );
  function wbbm_order_status_before_payment($order_id, $posted_data, $order)
  {
    $order->update_status('processing');
  }




  function wbbm_get_all_stops_after_this($bus_id, $val, $end)
  {
    $start_stops = get_post_meta($bus_id, 'wbbm_bus_bp_stops', true);
    $all_stops = array();
    foreach ($start_stops as $_start_stops) {
      $all_stops[] = $_start_stops['wbbm_bus_bp_stops_name'];
    }
    $pos        = array_search($val, $all_stops);
    $pos2       = array_search($end, $all_stops);
    unset($all_stops[$pos]);
    unset($all_stops[$pos2]);
    return $all_stops;
  }


  function wbbm_add_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, $user_name, $user_email, $user_phone, $user_gender, $user_address, $user_type, $b_time, $j_time, $adult, $adult_per_price, $child, $child_per_price, $total_price, $item_quantity, $j_date, $add_datetime, $status)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wbbm_bus_booking_list';
    $add_datetime = current_time("Y-m-d h:i:s");
    $wpdb->insert(
      $table_name,
      array(
        'order_id'        => $order_id,
        'bus_id'          => $bus_id,
        'user_id'         => $user_id,
        'boarding_point'  => $start,
        'next_stops'      => $next_stops,
        'droping_point'   => $end,
        'user_name'       => $user_name,
        'user_email'      => $user_email,
        'user_phone'      => $user_phone,
        'user_gender'     => $user_gender,
        'user_address'    => $user_address,
        'user_type'       => $user_type,
        'bus_start'       => $b_time,
        'user_start'      => $j_time,
        'total_adult'     => $adult,
        'per_adult_price' => $adult_per_price,
        'total_child'     => $child,
        'per_child_price' => $child_per_price,
        'total_price'     => $total_price,
        'seat'            => $item_quantity,
        'journey_date'    => $j_date,
        'booking_date'    => $add_datetime,
        'status'          => $status
      ),
      array(
        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%d',
        '%d',
        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s',
        '%d'
      )
    );
  }



  add_action('woocommerce_checkout_order_processed', 'wbbm_add_passenger_to_db',  1, 1);
  function wbbm_add_passenger_to_db($order_id)
  {
    global $wpdb;
    // Getting an instance of the order object
    $order      = wc_get_order($order_id);
    $order_meta = get_post_meta($order_id);

    # Iterating through each order items (WC_Order_Item_Product objects in WC 3+)
    foreach ($order->get_items() as $item_id => $item_values) {
      $product_id = $item_values->get_product_id();
      $item_data = $item_values->get_data();
      $product_id = $item_data['product_id'];
      $item_quantity = $item_values->get_quantity();
      $product = get_page_by_title($item_data['name'], OBJECT, 'wbbm_bus');
      $event_name = $item_data['name'];
      $event_id = $product->ID;
      $item_id = $item_id;
      // $item_data = $item_values->get_data();

      $user_id          = $order_meta['_customer_user'][0];
      $order_status     = $order->status;
      $eid              = wbbm_get_order_meta($item_id, '_wbbm_bus_id');

      if (get_post_type($eid) == 'wbbm_bus') {
        $user_info_arr      = wbbm_get_order_meta($item_id, '_wbbm_passenger_info');
        $start              = wbbm_get_order_meta($item_id, 'Start');
        $end                = wbbm_get_order_meta($item_id, 'End');
        $j_date             = wbbm_get_order_meta($item_id, 'Date');
        $j_time             = wbbm_get_order_meta($item_id, 'Time');
        $bus_id             = wbbm_get_order_meta($item_id, '_bus_id');
        $b_time             = wbbm_get_order_meta($item_id, '_btime');

        $adult             = wbbm_get_order_meta($item_id, 'Adult');
        $child             = wbbm_get_order_meta($item_id, 'Child');
        $adult_per_price   = wbbm_get_order_meta($item_id, '_adult_per_price');
        $child_per_price   = wbbm_get_order_meta($item_id, '_child_per_price');
        $total_price       = wbbm_get_order_meta($item_id, '_total_price');
        $next_stops        = maybe_serialize(wbbm_get_all_stops_after_this($bus_id, $start, $end));

        $usr_inf            = unserialize($user_info_arr);
        $counter            = 0;
        $_seats             = 'None';

        $item_quantity  = ($adult + $child);
        // $_seats         =   $item_quantity;
        // foreach ($seats as $_seats) {
        for ($x = 1; $x <= $item_quantity; $x++) {

          // if(!empty($_seats)){

          if ($usr_inf[$counter]['wbbm_user_name']) {
            $user_name = $usr_inf[$counter]['wbbm_user_name'];
          } else {
            $user_name = "";
          }
          if ($usr_inf[$counter]['wbbm_user_email']) {
            $user_email = $usr_inf[$counter]['wbbm_user_email'];
          } else {
            $user_email = "";
          }
          if ($usr_inf[$counter]['wbbm_user_phone']) {
            $user_phone = $usr_inf[$counter]['wbbm_user_phone'];
          } else {
            $user_phone = "";
          }
          if ($usr_inf[$counter]['wbbm_user_address']) {
            $user_address = $usr_inf[$counter]['wbbm_user_address'];
          } else {
            $user_address = "";
          }
          if ($usr_inf[$counter]['wbbm_user_gender']) {
            $user_gender = $usr_inf[$counter]['wbbm_user_gender'];
          } else {
            $user_gender = "";
          }
          if ($usr_inf[$counter]['wbbm_user_type']) {
            $user_type = $usr_inf[$counter]['wbbm_user_type'];
          } else {
            $user_type = "Adult/Child";
          }
          $_seats = $item_quantity;
          $check_before_add       = wbbm_get_order_seat_check($bus_id, $order_id, $user_type, $b_time, $j_date);
          if ($check_before_add == 0) {

            wbbm_add_passenger($order_id, $bus_id, $user_id, $start, $next_stops, $end, $user_name, $user_email, $user_phone, $user_gender, $user_address, $user_type, $b_time, $j_time, $adult, $adult_per_price, $child, $child_per_price, $total_price, $item_quantity, $j_date, $add_datetime, 0);
          }
          // }
          $counter++;
        }
      }
    }
  }




  add_action('woocommerce_order_status_changed', 'wbbm_bus_ticket_seat_management', 10, 4);
  function wbbm_bus_ticket_seat_management($order_id, $from_status, $to_status, $order)
  {
    global $wpdb;
    // Getting an instance of the order object
    $order      = wc_get_order($order_id);
    $order_meta = get_post_meta($order_id);

    # Iterating through each order items (WC_Order_Item_Product objects in WC 3+)
    foreach ($order->get_items() as $item_id => $item_values) {
      $product_id = $item_values->get_product_id();
      $item_data = $item_values->get_data();
      $product_id = $item_data['product_id'];
      $item_quantity = $item_values->get_quantity();
      $product = get_page_by_title($item_data['name'], OBJECT, 'wbbm_bus');
      $event_name = $item_data['name'];
      $event_id = $product->ID;
      $item_id = $item_id;
      // $item_data = $item_values->get_data();

      $user_id          = $order_meta['_customer_user'][0];
      $order_status     = $order->status;
      $eid              = wbbm_get_order_meta($item_id, '_wbbm_bus_id');

      if (get_post_type($eid) == 'wbbm_bus') {


        $user_info_arr      = wbbm_get_order_meta($item_id, '_wbbm_passenger_info');
        $start              = wbbm_get_order_meta($item_id, 'Start');
        $end                = wbbm_get_order_meta($item_id, 'End');
        $j_date             = wbbm_get_order_meta($item_id, 'Date');
        $j_time             = wbbm_get_order_meta($item_id, 'Time');
        $bus_id             = wbbm_get_order_meta($item_id, '_bus_id');
        $b_time             = wbbm_get_order_meta($item_id, '_btime');

        $adult             = wbbm_get_order_meta($item_id, 'Adult');
        $child             = wbbm_get_order_meta($item_id, 'Child');
        $adult_per_price   = wbbm_get_order_meta($item_id, '_adult_per_price');
        $child_per_price   = wbbm_get_order_meta($item_id, '_child_per_price');
        $total_price       = wbbm_get_order_meta($item_id, '_total_price');
        $next_stops        = maybe_serialize(wbbm_get_all_stops_after_this($bus_id, $start, $end));


        $usr_inf            = unserialize($user_info_arr);
        $counter            = 0;
        $_seats             = 'None';

        $item_quantity  = ($adult + $child);
        // $_seats         =   $item_quantity;
        // foreach ($seats as $_seats) {
        for ($x = 1; $x <= $item_quantity; $x++) {

          // if(!empty($_seats)){

          if ($usr_inf[$counter]['wbbm_user_name']) {
            $user_name = $usr_inf[$counter]['wbbm_user_name'];
          } else {
            $user_name = "";
          }
          if ($usr_inf[$counter]['wbbm_user_email']) {
            $user_email = $usr_inf[$counter]['wbbm_user_email'];
          } else {
            $user_email = "";
          }
          if ($usr_inf[$counter]['wbbm_user_phone']) {
            $user_phone = $usr_inf[$counter]['wbbm_user_phone'];
          } else {
            $user_phone = "";
          }
          if ($usr_inf[$counter]['wbbm_user_address']) {
            $user_address = $usr_inf[$counter]['wbbm_user_address'];
          } else {
            $user_address = "";
          }
          if ($usr_inf[$counter]['wbbm_user_gender']) {
            $user_gender = $usr_inf[$counter]['wbbm_user_gender'];
          } else {
            $user_gender = "";
          }
          if ($usr_inf[$counter]['wbbm_user_type']) {
            $user_type = $usr_inf[$counter]['wbbm_user_type'];
          } else {
            $user_type = "Adult";
          }
          $_seats = $item_quantity;
          $check_before_add       = wbbm_get_order_seat_check($bus_id, $order_id, $user_type, $b_time, $j_date);
          // }
          $counter++;
        }








        if ($order->has_status('processing') || $order->has_status('pending') || $order->has_status('on-hold')) {

          // if($order_status=='processing'||$order_status=='pending'||$order_status=='on-hold'){

          $status = 1;
          $table_name = $wpdb->prefix . 'wbbm_bus_booking_list';
          $wpdb->query($wpdb->prepare("UPDATE $table_name
                SET status = %d
             WHERE order_id = %d
             AND bus_id = %d", $status, $order_id, $event_id));
        }




        if ($order->has_status('cancelled')) {
          $status = 3;
          $table_name = $wpdb->prefix . 'wbbm_bus_booking_list';
          $wpdb->query($wpdb->prepare("UPDATE $table_name
                SET status = %d
             WHERE order_id = %d
             AND bus_id = %d", $status, $order_id, $event_id));
        }



        if ($order->has_status('completed')) {

          $status = 2;
          $table_name = $wpdb->prefix . 'wbbm_bus_booking_list';
          $wpdb->query($wpdb->prepare("UPDATE $table_name
                SET status = %d
             WHERE order_id = %d
             AND bus_id = %d", $status, $order_id, $event_id));
        }
      }
    }
  }


  function wbbm_array_strip($string, $allowed_tags = NULL)
  {
    if (is_array($string)) {
      foreach ($string as $k => $v) {
        $string[$k] = wbbm_array_strip($v, $allowed_tags);
      }
      return $string;
    }
    return strip_tags($string, $allowed_tags);
  }


  function wbbm_find_product_in_cart($id)
  {

    $product_id = $id;
    $in_cart = false;

    foreach (WC()->cart->get_cart() as $cart_item) {
      $product_in_cart = $cart_item['product_id'];
      if ($product_in_cart === $product_id) $in_cart = true;
    }

    if ($in_cart) {
      return 'into-cart';
    } else {
      return 'not-in-cart';
    }
  }



  add_action('show_seat_form', 'wbbm_seat_form');
  function wbbm_seat_form($start, $end, $price_arr)
  {
    $date = $return ? mage_get_isset('r_date') : mage_get_isset('j_date');
    $available_seat =     mage_available_seat(wbbm_convert_date_to_php($date));
    $id = get_the_id();
    $boarding  = isset($_GET['bus_start_route']) ? strip_tags($_GET['bus_start_route']) : '';
    $dropping  = isset($_GET['bus_end_route']) ? strip_tags($_GET['bus_end_route']) : '';
    $seat_price_adult = mage_seat_price($id, $boarding, $dropping, true);
    $seat_price_child = mage_seat_price($id, $boarding, $dropping, false);
    ob_start();
  ?>
    <div class="seat-no-form">
      <?php
      $adult_fare =  wbbm_get_bus_price($start, $end, $price_arr);
      if ($adult_fare > 0) {
      ?>
        <label for='quantity_<?php echo get_the_id(); ?>'>
          Adult (<?php //echo get_woocommerce_currency_symbol();
                  ?><?php echo wc_price($seat_price_adult); ?> )
          <input type="number" id="quantity_<?php echo get_the_id(); ?>" class="input-text qty text bqty" step="1" min="0" max="<?php echo $available_seat; ?>" name="adult_quantity" value="" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric" required aria-labelledby="" placeholder='0' />
        </label>
      <?php
      }
      $child_fare =  wbbm_get_bus_price_child($start, $end, $price_arr);
      if ($child_fare > 0) {
      ?>
        <label for='child_quantity_<?php echo get_the_id(); ?>'>
          Child (<?php //echo get_woocommerce_currency_symbol();
                  ?><?php echo wc_price($seat_price_child); ?>)
          <input type="number" id="child_quantity_<?php echo get_the_id(); ?>" class="input-text qty text bqty" step="1" min="0" max="<?php echo $available_seat; ?>" name="child_quantity" value="0" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric" required aria-labelledby="" placeholder='0' />
        </label>
      <?php } ?>
    </div>
  <?php
    $seat_form = ob_get_clean();
    echo $seat_form;
  }

  function wbbm_check_od_in_range($start_date, $end_date, $j_date)
  {
    // Convert to timestamp
    $start_ts = strtotime($start_date);
    $end_ts = strtotime($end_date);
    $user_ts = strtotime($j_date);

    // Check that user date is between start & end
    if (($user_ts >= $start_ts) && ($user_ts <= $end_ts)) {
      return 'yes';
    } else {
      return 'no';
    }
  }


  add_filter('woocommerce_cart_item_price', 'wbbm_avada_mini_cart_price_fixed', 100, 3);
  function wbbm_avada_mini_cart_price_fixed($price, $cart_item, $r)
  {
    $price = wc_price($cart_item['line_total']);
    return $price;
  }



  /**
   * The magical Datetime Function, Just call this function where you want display date or time, Pass the date or time and the format this will be return the date or time in the current wordpress saved datetime format and according the timezone.
   */
  function get_wbbm_datetime($date, $type)
  {
    $date_format        = get_option('date_format');
    $time_format        = get_option('time_format');
    $wpdatesettings     = $date_format . '  ' . $time_format;
    $timezone           = wp_timezone_string();
    $timestamp          = strtotime($date . ' ' . $timezone);

    if ($type == 'date') {
      return wp_date($date_format, $timestamp);
    }
    if ($type == 'date-time') {
      return wp_date($wpdatesettings, $timestamp);
    }
    if ($type == 'date-text') {

      return wp_date($date_format, $timestamp);
    }

    if ($type == 'date-time-text') {
      return wp_date($wpdatesettings, $timestamp);
    }
    if ($type == 'time') {
      return wp_date($time_format, $timestamp);
    }

    if ($type == 'time-raw') {
      return wp_date('h:i A', $timestamp);
    }

    if ($type == 'day') {
      return wp_date('d', $timestamp);
    }
    if ($type == 'month') {
      return wp_date('M', $timestamp);
    }
  }


  function wbbm_get_page_list()
  {
    $args = array(
      'post_type' => 'page',
      'posts_per_page' => -1
    );

    $loop = new WP_Query($args);
    $page = [];
    foreach ($loop->posts as $_page) {
      # code...
      $page[$_page->post_name] = $_page->post_title;
    }
    return $page;
  }



  function wbbm_convert_datepicker_dateformat()
  {
    $date_format        = get_option('date_format');
    $php_d   = array('F', 'j', 'Y', 'm', 'd', 'D', 'M', 'y');
    $js_d   = array('d', 'M', 'yy', 'mm', 'dd', 'D', 'M', 'y');
    $dformat = str_replace($php_d, $js_d, $date_format);
    if ($date_format == 'Y-m-d' || $date_format == 'm/d/Y' || $date_format == 'm/d/Y') {
      return $dformat;
    } else {
      return 'yy-mm-dd';
    }
  }

  function wbbm_convert_date_to_php($date)
  {

    $date_format        = get_option('date_format');
    if ($date_format == 'Y-m-d' || $date_format == 'm/d/Y' || $date_format == 'm/d/Y') {
      if ($date_format == 'd/m/Y') {
        $date = str_replace('/', '-', $date);
      }
    }
    return date('Y-m-d', strtotime($date));
  }





  // Function for create hidden product for bus
  function wbbm_create_hidden_event_product($post_id, $title)
  {
    $new_post = array(
      'post_title'    =>   $title,
      'post_content'  =>   '',
      'post_name'     =>   uniqid(),
      'post_category' =>   array(),
      'tags_input'    =>   array(),
      'post_status'   =>   'publish',
      'post_type'     =>   'product'
    );


    $pid                = wp_insert_post($new_post);

    update_post_meta($post_id, 'link_wc_product', $pid);
    update_post_meta($pid, 'link_wbbm_bus', $post_id);
    update_post_meta($pid, '_price', 0.01);

    update_post_meta($pid, '_sold_individually', 'yes');
    update_post_meta($pid, '_virtual', 'yes');
    $terms = array('exclude-from-catalog', 'exclude-from-search');
    wp_set_object_terms($pid, $terms, 'product_visibility');
    update_post_meta($post_id, 'check_if_run_once', true);
  }



  function wbbm_on_post_publish($post_id, $post, $update)
  {
    if ($post->post_type == 'wbtm_bus' && $post->post_status == 'publish' && empty(get_post_meta($post_id, 'check_if_run_once'))) {

      // ADD THE FORM INPUT TO $new_post ARRAY
      $new_post = array(
        'post_title'    =>   $post->post_title,
        'post_content'  =>   '',
        'post_name'     =>   uniqid(),
        'post_category' =>   array(),  // Usable for custom taxonomies too
        'tags_input'    =>   array(),
        'post_status'   =>   'publish', // Choose: publish, preview, future, draft, etc.
        'post_type'     =>   'product'  //'post',page' or use a custom post type if you want to
      );
      //SAVE THE POST
      $pid                = wp_insert_post($new_post);
      $product_type     = mep_get_option('mep_event_product_type', 'general_setting_sec', 'yes');
      update_post_meta($post_id, 'link_wc_product', $pid);
      update_post_meta($pid, 'link_wbbm_bus', $post_id);
      update_post_meta($pid, '_price', 0.01);
      update_post_meta($pid, '_sold_individually', 'yes');
      update_post_meta($pid, '_virtual', $product_type);
      $terms = array('exclude-from-catalog', 'exclude-from-search');
      wp_set_object_terms($pid, $terms, 'product_visibility');
      update_post_meta($post_id, 'check_if_run_once', true);
    }
  }
  add_action('wp_insert_post',  'wbbm_on_post_publish', 10, 3);

  function wbbm_count_hidden_wc_product($event_id)
  {
    $args = array(
      'post_type'      => 'product',
      'posts_per_page' => -1,
      'meta_query' => array(
        array(
          'key'       => 'link_wbbm_bus',
          'value'     => $event_id,
          'compare'   => '='
        )
      )
    );
    $loop = new WP_Query($args);
    // print_r($loop->posts);
    return $loop->post_count;
  }


  add_action('save_post', 'wbbm_wc_link_product_on_save', 99, 1);
  function wbbm_wc_link_product_on_save($post_id)
  {

    if (get_post_type($post_id) == 'wbbm_bus') {

      //   if ( ! isset( $_POST['mep_event_reg_btn_nonce'] ) ||
      //   ! wp_verify_nonce( $_POST['mep_event_reg_btn_nonce'], 'mep_event_reg_btn_nonce' ) )
      //     return;

      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

      if (!current_user_can('edit_post', $post_id))
        return;
      $event_name = get_the_title($post_id);

      if (wbbm_count_hidden_wc_product($post_id) == 0 || empty(get_post_meta($post_id, 'link_wc_product', true))) {
        wbbm_create_hidden_event_product($post_id, $event_name);
      }

      $product_id = get_post_meta($post_id, 'link_wc_product', true) ? get_post_meta($post_id, 'link_wc_product', true) : $post_id;
      set_post_thumbnail($product_id, get_post_thumbnail_id($post_id));
      wp_publish_post($product_id);

      // $product_type               = mep_get_option('mep_event_product_type', 'general_setting_sec','yes');

      $_tax_status                = isset($_POST['_tax_status']) ? strip_tags($_POST['_tax_status']) : 'none';
      $_tax_class                 = isset($_POST['_tax_class']) ? strip_tags($_POST['_tax_class']) : '';

      update_post_meta($product_id, '_tax_status', $_tax_status);
      update_post_meta($product_id, '_tax_class', $_tax_class);
      update_post_meta($product_id, '_stock_status', 'instock');
      update_post_meta($product_id, '_manage_stock', 'no');
      update_post_meta($product_id, '_virtual', 'yes');
      update_post_meta($product_id, '_sold_individually', 'yes');



      // Update post
      $my_post = array(
        'ID'           => $product_id,
        'post_title'   => $event_name, // new title
        'post_name' =>  uniqid() // do your thing here
      );

      // unhook this function so it doesn't loop infinitely
      remove_action('save_post', 'wbbm_wc_link_product_on_save');
      // update the post, which calls save_post again
      wp_update_post($my_post);
      // re-hook this function
      add_action('save_post', 'wbbm_wc_link_product_on_save');
      // Update the post into the database


    }
  }



  add_action('parse_query', 'wbbm_product_tags_sorting_query');
  function wbbm_product_tags_sorting_query($query)
  {
    global $pagenow;

    $taxonomy  = 'product_visibility';

    $q_vars    = &$query->query_vars;

    if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == 'product') {


      $tax_query = array([
        'taxonomy' => 'product_visibility',
        'field' => 'slug',
        'terms' => 'exclude-from-catalog',
        'operator' => 'NOT IN',
      ]);
      $query->set('tax_query', $tax_query);
    }
  }
} else {
  function wbbm_admin_notice_wc_not_active()
  {
    $class = 'notice notice-error';
    $message = __('Multipurpose Ticket Booking Manager  Plugin is Dependent on WooCommerce, But currently WooCommerce is not Active. Please Active WooCommerce plugin first', 'bus-booking-manager');
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
  }
  add_action('admin_notices', 'wbbm_admin_notice_wc_not_active');
}

/**
 * FAST customization - TH
 */
function wbbm_build_reports()
{
  global $wpdb;
  $table_name = $wpdb->prefix . "wbbm_bus_booking_list";

  $table_name = $wpdb->prefix . "wbbm_bus_booking_list";
  $result = $wpdb->get_results("SELECT * FROM $table_name");

  $date = strtotime($_GET['j_date']) ?: time();

  // getdate(strtotime('2020-04-20'));

  /*   echo '<pre>';

  foreach ($result as $order) {
    var_dump($order);
    echo '<br>';
  }
  echo '</pre>'; */

  $dateComponents = getdate($date);

  // var_dump($dateComponents);
  $month = $dateComponents['mon'];
  $year = $dateComponents['year'];
  $day = $dateComponents['mday'];

  $monthBuild = $month < 10 ? "0" . $month : $month;
  $dayBuild = $day < 10 ? "0" . $day : $day;
  $dateBuild = $year . "-" . $monthBuild . "-" . $dayBuild;

  echo '<div class="locations-container">';
  echo build_calendar($month, $year, $dateBuild);
  // echo showDirectionDropdown();
  // echo '</div>';
  echo '<div class="table-container">';
  echo showBusesAndBookings(false,$dateBuild);
  echo showBusesAndBookings(true, $dateBuild);
  echo '</div>';
}

function build_calendar($month, $year, $dateBuild)
{

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

  addCalendarScripts();
  echo $calendar;
}

function showBusesAndBookings($return=false)
{
  $start = mage_get_isset('bus_start_route') ?: 'Ft Collins Harmony Transfer Center';
  $end = mage_get_isset('bus_end_route') ?: 'DIA';

  $real_start = $return ? $end : $start;
  $real_end = $return ? $start : $end;

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
            'value' => $real_start,
            'compare' => 'LIKE',
        ),

        array(
            'key' => 'wbbm_bus_next_stops',
            'value' => $real_end,
            'compare' => 'LIKE',
        ),
    )
  );

  $loop = new WP_Query($arr);

  if ($loop->post_count == 0) {
    ?>
      <div class='wbbm_error' style='text-align:center'>
          <span><?php _e('Sorry, No Bus Found', 'bus-booking-manager'); ?></span>
      </div>
  <?php
  } else {
    $j_date = mage_get_isset('j_date');
    echo $return ? '<h3>Northbound</h3>' : '<h3>Southbound</h3>';
    $table = '<table class="table">';
    $table .= '<thead><th>Shuttle</th><th>Seats Sold</th><th>Pickups</th><th>Manifest</th></thead>';

    $table .= "<tbody>";
    while ($loop->have_posts()) {
      $loop->the_post();

      $id = get_the_id();
      $title = the_title('','',false);
      $boarding = mage_get_isset('bus_start_route') ?: 'Ft Collins Harmony Transfer Center';
      $dropping = mage_get_isset('bus_end_route') ?: 'DIA';

      $pickups = wbbm_get_pickup_number($id, wbbm_convert_date_to_php(mage_get_isset('j_date')));

      $values = get_post_custom(get_the_id());
      $total_seat = $values['wbbm_total_seat'][0];

      $available_seat     = mage_available_seat(wbbm_convert_date_to_php(mage_get_isset('j_date')));
      $seat_price_adult   = mage_seat_price($id,$boarding,$dropping,true);
      $boarding_time      = get_wbbm_datetime(boarding_dropping_time(false, false),'time');

      $sold_seats         = $total_seat - $available_seat;

      // echo $title.'<br>';
      $table .= "<tr><td>$title</td><td>$sold_seats</td><td>";

      foreach ($pickups as $p) {
        global $wpdb;
        $table_name = $wpdb->prefix . "wbbm_bus_booking_list";

        $query = "SELECT DISTINCT order_id, COUNT(order_id) as tickets_purchased FROM $table_name WHERE boarding_point='$p->boarding_point' AND journey_date='$j_date' AND (status=2 OR status=1) GROUP BY order_id";

        $order_ids = $wpdb->get_results($query);

        $name_build = '';
        if ($order_ids) {
          foreach ($order_ids as $o_id) {

            $name_build .= '<div class="attendee-list" data-id="'. $id .'">';

            $name = "<div data-id='$o_id->order_id'>";
            $order = wc_get_order($o_id->order_id);
            $name .= $order->get_billing_first_name();
            $name .= ' ' . $order->get_billing_last_name();

            $name_build .= $name.', '.$o_id->tickets_purchased.' Ticket(s)</div>';
            $name_build .= '</div>';
          }
        } else {
          $name_build = '<div>No Seats Booked</div>';
        }

        $table .= "<div>$p->boarding_point: $p->riders</div>";
        // $table .= "<div class='rider-details'>$name_build</div>";

      }
      $empty = false;
      if (!$pickups) {
        $empty = true;
        $table .= '<div>No Seats Booked</div>';
      }

      $table .= "</td>";

      $table .= "</td><td>";
      if (!$empty) {
        $table .= "<button class='button button-primary'><div class='wp-menu-image dashicons-before dashicons-admin-generic generate-report' data-id='$id' data-title='$title'></div></button></td></tr>";
      }
    }

    $table .= "</tbody></table>";

    echo $table;
  }


}


function addCalendarScripts()
{
  ob_start();
  ?>
  <script>
    var urlParams = new URLSearchParams(window.location.search);

    const day_is_set = urlParams.get('j_date');
    const start_is_set = urlParams.get('bus_start_route');
    const end_is_set = urlParams.get('bus_end_route');

    jQuery('document').ready(function() {
      let date = new Date().toISOString().slice(0,10);
      if (!day_is_set) window.location = `${window.location.href}&j_date=${date}`;
      if (!start_is_set) window.location = `${window.location.href}&bus_start_route=Ft+Collins+Harmony+Transfer+Center`;
      if (!end_is_set) window.location = `${window.location.href}&bus_end_route=DIA`;

    })

    jQuery('body').off('click', '.day');
    jQuery('body').on('click', '.day', function() {
      const date = jQuery(this).attr('rel');

      if (day_is_set) {
        urlParams.set('j_date', date);
        window.location = `${window.location.origin}${window.location.pathname}?${urlParams.toString()}`;
      } else {
        window.location = `${window.location.href}&j_date=${date}`;
      }
    });
    jQuery('body').off('change', '.stop-select');
    jQuery('body').on('change', '.stop-select', function() {
      const direction = jQuery(this).attr('id') === 'from_stop' ? 'from' : 'to';
      const val = jQuery(this).val();

      if (direction === 'from') {
        if (start_is_set) {
          urlParams.set('bus_start_route', val);
          window.location = `${window.location.origin}${window.location.pathname}?${urlParams.toString()}`;
        } else {
          window.location = `${window.location.href}&bus_start_route=${val}`;
        }
      } else {
        if (end_is_set) {
          urlParams.set('bus_end_route', val);
          window.location = `${window.location.origin}${window.location.pathname}?${urlParams.toString()}`;
        } else {
          window.location = `${window.location.href}&bus_end_route=${val}`;
        }
      }
    });
    jQuery('body').off('click', '.month-change');
    jQuery('body').on('click', '.month-change', function() {
      const direction = jQuery(this).attr('data-direction');
      console.log(day_is_set);

      let prevDate = day_is_set ? new Date(day_is_set) : new Date();

      let newDate = direction === 'next' ? new Date(prevDate.setMonth(prevDate.getMonth() + 1)) : new Date(prevDate.setMonth(prevDate.getMonth() - 1));

      let formatted = newDate.toISOString().slice(0,10);

      if (day_is_set) {
        urlParams.set('j_date', formatted);
        window.location = `${window.location.origin}${window.location.pathname}?${urlParams.toString()}`;
      } else {
        window.location = `${window.location.href}&j_date=${formatted}`;
      }

    });
    jQuery('body').off('click', '.day-change');
    jQuery('body').on('click', '.day-change', function() {
      const direction = jQuery(this).attr('data-direction');
      console.log(day_is_set);

      let prevDate = day_is_set ? new Date(day_is_set) : new Date();

      let newDate = direction === 'next' ? new Date(prevDate.setDate(prevDate.getDate() + 1)) : new Date(prevDate.setDate(prevDate.getDate() - 1));

      let formatted = newDate.toISOString().slice(0,10);

      if (day_is_set) {
        urlParams.set('j_date', formatted);
        window.location = `${window.location.origin}${window.location.pathname}?${urlParams.toString()}`;
      } else {
        window.location = `${window.location.href}&j_date=${formatted}`;
      }

    });

    jQuery('body').off('click', '.today');
    jQuery('body').on('click', '.today', function() {
      let date = new Date();
      let formatted = date.toISOString().slice(0,10);

      if (day_is_set) {
        urlParams.set('j_date', formatted);
        window.location = `${window.location.origin}${window.location.pathname}?${urlParams.toString()}`;
      } else {
        window.location = `${window.location.href}&j_date=${formatted}`;
      }
    });

    // jQuery('body').off('click', '.generate-report');
    jQuery('body').on('click', '.generate-report', function() {
      const id = jQuery(this).attr('data-id');
      const title = jQuery(this).attr('data-title');

      // console.log(`${window.location.href}&bus_id=${id}&download_list=Y`);
      window.open(`${window.location.href}&bus_id=${id}&download_list=Y&title=${title}`, '_blank');

      /* const data = {
        id,
        action: 'generate_report',
        j_date: day_is_set,
        bus_start_route: start_is_set,
        bus_end_route: end_is_set,
      };

      jQuery.post(ajaxurl, data, function(response) {
        console.log(response);
      }) */
    })


  </script>
<?php
  $script = ob_get_clean();
  echo $script;
}

function showDirectionDropdown()
{
  $from = mage_get_isset('bus_start_route') ?: 'Ft Collins Harmony Transfer Center';
  $to = mage_get_isset('bus_end_route') ?: 'DIA';

  $routes = get_terms(array(
    'taxonomy' => 'wbbm_bus_stops',
    'hide_empty' => false,
  ));


  $build = '<div>';
  $build = '<div>';
  $build .= '<label>From:</label><br>';
  $build .= '<select id="from_stop" class="stop-select">';
  foreach ($routes as $route) {
    $selected = $route->name === $from ? 'selected' : '';
    $build .= "<option value='$route->name' $selected>$route->name</option>";
  }
  $build .= '</select>';
  $build .= '</div>';
  $build .= '<div>';
  $build .= '<label>To:</label><br>';
  $build .= '<select id="to_stop" class="stop-select">';
  foreach ($routes as $route) {
    $selected = $route->name === $to ? 'selected' : '';
    $build .= "<option value='$route->name' $selected>$route->name</option>";
  }
  $build .= '</select>';
  $build .= '</div>';
  $build .= '</div>';

  return $build;
}

function wbbm_generate_csv($arr, $title) {
  $direction = mage_get_isset('bus_end_route') === 'DIA' ? 'Southbound' : 'Northbound';
  // wbbm_download_send_headers("data_export_" . date("Y-m-d") . ".csv");
  print wbbm_array2csv($arr, "$title " . mage_get_isset('j_date') . ".csv");
  // wp_die();
}

function wbbm_get_pickup_number($bus_id, $date)
{
  global $wpdb;
  $table_name = $wpdb->prefix . "wbbm_bus_booking_list";

  /* $order = wc_get_order(212);
  echo '<pre>';
  var_dump($order->get_billing_first_name());
  echo $order->get_billing_last_name();
  echo '</pre>';
  die(); */

  $query = "SELECT boarding_point, COUNT(booking_id) as riders FROM $table_name WHERE bus_id='$bus_id' AND journey_date='$date' AND (status=2 OR status=1) GROUP BY boarding_point, bus_start ORDER BY bus_start ASC";

  $riders_by_location = $wpdb->get_results($query);

  return $riders_by_location;
}

function wbbm_array2csv(array &$array, $filename)
{
   if (count($array) == 0) {
     return null;
   }

  //  header( 'Content-Type: text/csv' ); // tells browser to download
  //  header( 'Content-Disposition: attachment; filename="' . $filename .'"' );
  //  header( 'Pragma: no-cache' ); // no cache
  //  header( "Expires: Sat, 26 Jul 1997 05:00:00 GMT" ); // expire date

   $csv = "";


   $fh = @fopen( 'php://output', 'w' );
   fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
   header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
   header( 'Content-Description: File Transfer' );
   header( 'Content-type: text/csv' );
   header( "Content-Disposition: attachment; filename={$filename}" );
   header( 'Expires: 0' );
   header( 'Pragma: public' );
  //  fputcsv( $fh, $header_row );

  //  fputcsv($df, array_keys(reset($array)));
   foreach ($array as $row) {
      fputcsv($fh, $row);
      // $csv .= implode( ',', $row );
      // $csv .= "\n";
    }
    fclose( $fh );

    ob_end_flush();

    // echo '<pre>';
    // var_dump($csv);
    // wp_die();
    // echo '</pre>';
  //  return ob_end_flush();
}

function wbbm_download_send_headers($filename) {
  // disable caching
  // $now = gmdate("D, d M Y H:i:s");
  // header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
  // header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
  // header("Last-Modified: {$now} GMT");

  // // force download
  // header("Content-Type: application/force-download");
  // header("Content-Type: application/octet-stream");
  // header("Content-Type: application/download");

  // // disposition / encoding on response body
  // header("Content-Disposition: attachment;filename={$filename}");
  // header("Content-Transfer-Encoding: binary");

  header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
  header( 'Content-Description: File Transfer' );
  header( 'Content-type: text/csv' );
  header( "Content-Disposition: attachment; filename={$filename}" );
  header( 'Expires: 0' );
  header( 'Pragma: public' );

}
