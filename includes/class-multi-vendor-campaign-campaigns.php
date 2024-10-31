<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/includes
 */

use WeDevs\Dokan\Commission;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/includes
 * @author     Your Name <email@example.com>
 */
class Multi_Vendor_Campaign_Campaigns {

    /**
     * Check if the Pro version of the plugin is active
    */
    public static function is_mvc_pro_active() {
        return class_exists( 'Mvc_Pro' );
    }

    /**
     * get commission percentage of a product from DB
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return
     */
    private function get_commission_percentage($product_id)
    {
        $product_campaign = $this->get_campaign_of_product($product_id);
        $campaign_commission = 0;
        if( $this->valid_product_campaign( $product_campaign ) )
        {
            $campaign_commission = $product_campaign->commission_percentage;
        }

        return $campaign_commission;
    }
    /**
     * Load the required dependencies for this plugin.
     *
     * - Multi_Vendor_Campaign_Loader. Orchestrates the hooks of the plugin.
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     * @param $commission
     * @param $product_id
     * @return
     */
	public function wcv_commission_rate_percent($commission, $product_id ) {
        if ( is_wcv_pro_active() ) return $commission;

		$product_campaign = $this->get_campaign_of_product($product_id);

		if( $this->valid_product_campaign( $product_campaign ) )
		{
            $commission = $commission + $product_campaign->commission_percentage;
		}

		return $commission;
	}

	public function wcv_pro_commission_rate_percent( $commission_args, $product_id, $vendor_id ) {
        if ( $this->is_mvc_pro_active() ) return $commission_args;

        // Product campaign commissions
        $product_campaign = $this->get_campaign_of_product($product_id);

        if ( $this->valid_product_campaign($product_campaign) ) {
            if ( $commission_args['type'] == 'percent' ) {
                $commission_args['percent'] = $commission_args['percent'] + $product_campaign->commission_percentage;
            }
        }

        return $commission_args;
	}



    public function yith_commission_rate_display( $commission_id, $item_id, $commission_label, $order ) {
            global $wpdb;

            $item = $order->get_item($item_id);
            $_product = null;

            if( YITH_Vendors()->is_wc_2_7_or_greather && is_callable( array( $item, 'get_product' ) ) ){
                $product = $item->get_product();
            }

            else {
                $product = $order->get_product_from_item( $item );
            }

            $product_id = $product->get_id();

            $campaign_commission = (float) $this->get_commission_percentage($product_id)/100;

            $rate = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT rate FROM $wpdb->commissions WHERE ID = %d",
                    $commission_id
                )
            );

            $new_rate = $rate->rate + $campaign_commission;

            // Commission rate cannot be more than 100%
            if ( $new_rate >= 1 ) {
                $new_rate = 1;
            }

            $sql = $wpdb->prepare("UPDATE ". $wpdb->commissions ." SET rate = %f WHERE ID= %d", $new_rate, $commission_id);
            $result = $wpdb->query($sql);

    }

	public function yith_commission_rate_percent( $commission, $vendor, $order, $item, $item_id ) {

        // Init vars
        $_product = null;

        if( YITH_Vendors()->is_wc_2_7_or_greather && is_callable( array( $item, 'get_product' ) ) ) {
            $_product = $item->get_product();
        } else {
            $_product = $order->get_product_from_item( $item );
        }

        if( $_product->is_type( 'variation' ) ){
            $_variation = $_product;
            $_product = wc_get_product( yit_get_base_product_id( $_variation ) );
        }

        $product_campaign = $this->get_campaign_of_product($_product->get_id());
        if( $this->valid_product_campaign( $product_campaign ) ) {
            $campaign_commission = $product_campaign->commission_percentage;
            $campaign_commission = $campaign_commission / 100;
        }

        // Add campaign commission
        $commission += $campaign_commission;

        // Commission cannot be less than 0 or more than 1
        if      ( $commission < 0 )   $commission = 0;
        else if ( $commission > 1 )   $commission = 1;

        return $commission;
    }

    /**
     * Calculate commission of campaign products for Dokan plugin
     *
     * @since    1.0.0
     * @access   private
     * @param $earning
     * @param $product
     * @param $context
     * @return int $new_earning
     */
	public function dokan_commission_rate_percent( $earning, $product, $context ) {

        $product_id = $product->get_id();
        $product_price = $product->get_price();
        $seller_id = get_post_field( 'post_author', $product_id );

        $CommissionClass = new Commission();
        $original_earning = $CommissionClass->calculate_commission($product_id, $product_price, $seller_id);
        $new_earning = $original_earning;

        $product_campaign = $this->get_campaign_of_product($product_id);

        if ( $this->valid_product_campaign( $product_campaign ) ) {

            $seller_commission = dokan_get_seller_percentage( $seller_id, $product_id );
            $commission_type = dokan_get_commission_type( $seller_id, $product_id );
            $product_campaign = $this->get_campaign_of_product( $product_id );

            if ( $seller_commission < 0 ) return false;

            $product_quantity = $this->get_item_qty( $product );
            $campaign_commission = $product_campaign->commission_fee;

            if ( $commission_type == 'percentage' ) {

                $new_commission = $context == 'seller'
                    ? $seller_commission - $campaign_commission
                    : 100 - ($seller_commission - $campaign_commission);

                $product_price *= $product_quantity;
                $new_earning = ($product_price / 100) * $new_commission;

            } elseif ( $commission_type == 'flat' ) {

                $new_commission = $context == 'seller' 
                    ? ( $seller_commission + $campaign_commission ) * $product_quantity
                    : $product_price - ( $seller_commission + $campaign_commission );

                $new_earning = $context == 'seller'
                    ? $product_price - ( $new_commission * $product_quantity )
                    : ( $product_price - $new_commission ) * $product_quantity;

            }

            // Earning must be greater than 0 and less than product price
            // 0 < $new_earning < $product_price
            if ( $new_earning > $product_price ) {
                $new_earning = $product_price;
            } elseif ( $new_earning < 0 ) {
                $new_earning = 0;
            }

            return $new_earning;

        }

        // If the product isn't part of any campaign, just return $earning
        return $earning;

	}


    /**
     * Counts the quantity of specific ordered product in cart
     *
     * @param  object $product
     * @return int
     */
    public function get_item_qty( $product ) {
        $qty = 1;

        foreach( WC()->cart->get_cart() as $cart_item ) {

            $product_id = $product->get_parent_id();
    
            if( $product_id == 0 || empty( $product_id ) ) {
                $product_id = $product->get_id();
            }
    
            if ( $product_id == $cart_item['product_id'] ) {
                $qty = $cart_item['quantity'];
            }
            
        }

        return $qty;
    }
	
    /**
     * @param $commission
     * @param $product_id
     * @param $vendor
     * @param $variation_id
     * @param $item_id
     * @param $order
     * @return mixed
     */
    public function wc_marketplace_commission_rate_percent($commission, $product_id, $vendor, $variation_id, $item_id, $order) {
        global $WCMp;

        $commissionType = $WCMp->vendor_caps->payment_cap['commission_type'];

        $product_campaign = $this->get_campaign_of_product($product_id);

        if( $this->valid_product_campaign( $product_campaign ) )
        {
            if($commissionType == "fixed")
            {
                $commission['commission_val'] = $commission['commission_val'] + $product_campaign->commission_fixed;
            }
            elseif($commissionType == "percent" || $commissionType == "fixed_with_percentage_per_vendor")
            {
                $commission['commission_val'] = $commission['commission_val'] + $product_campaign->commission_percentage;
            }
            elseif ($commissionType == "fixed_with_percentage" || $commissionType == "fixed_with_percentage_qty")
            {
                $commission['commission_val'] = $commission['commission_val'] + $product_campaign->commission_percentage;
                $commission['commission_fixed'] = $commission['commission_fixed'] + $product_campaign->commission_fixed;
            }
        }

        return $commission;
    }

    public function wc_marketplace_variable_commission($amount, $product_id, $variation_id, $item, $order_id, $item_id) {
        global $WCMp;
        $commissionType = $WCMp->vendor_caps->payment_cap['commission_type'];
        $sharing_mode = $WCMp->vendor_caps->payment_cap['revenue_sharing_mode'];

        $product_campaign = $this->get_campaign_of_product($product_id);

        if ( $this->valid_product_campaign( $product_campaign ) ) {
            if ( $commissionType == "commission_by_product_price" || $commissionType == "commission_by_purchase_quantity"  ) {
                $product = wc_get_product( $product_id );
                $product_price = $product->get_price() * $this->get_item_qty( $product );

                if ( $sharing_mode == 'admin' ) {
                    $amount -= ( $product_price * $product_campaign->commission_percentage ) / 100;
                    $amount -= $product_campaign->commission_fixed;
                } else if ( $sharing_mode == 'vendor' ) {
                    $amount += ( $product_price * $product_campaign->commission_percentage ) / 100;
                    $amount += $product_campaign->commission_fixed;
                }

            } 
        }

        return $amount;
    }

    /**
     * @param $sale_price
     * @param $product
     * @return float|int
     */
    public function wc_get_sale_price($sale_price, $product) {

        $product_id = $product->get_id();

        if (empty($sale_price))
            $sale_price = $product->get_regular_price();

        global $wpdb;
        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
        $sql = $wpdb->prepare( "SELECT campaign_id FROM ". $table_campaign_product ." WHERE status = 1 AND product_id = %d", $product_id );

        $campaign = $wpdb->get_row( $sql );
        if($campaign){
            $table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';
            $sql = $wpdb->prepare( "SELECT enable, discount_value FROM ". $table_campaign ." WHERE id = %d", $campaign->campaign_id );
            $result = $wpdb->get_row( $sql );

            if(!$result->enable)
                return $sale_price;

            $discount_value = $result->discount_value;
            if($discount_value > 0){
                $discount_amount = $sale_price * ($discount_value/100);
                $sale_price      = $sale_price - $discount_amount;
                $decimals        = wc_get_price_decimals();
                $sale_price      = $this->wc_cart_round_discount($sale_price, $decimals);
            }

        }

        return $sale_price;
    }

    /**
     * @param $regular_price
     * @param $product
     * @return float|int
     */
    public function wc_get_regular_price($regular_price, $product) {

        $product_id = $product->get_id();

        global $wpdb;
        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
        $sql = $wpdb->prepare( "SELECT campaign_id FROM ". $table_campaign_product ." WHERE status = 1 AND product_id = %d", $product_id );

        $campaign = $wpdb->get_row( $sql );

        if($campaign){
            $table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';
            $sql = $wpdb->prepare( "SELECT enable, discount_value FROM ". $table_campaign ." WHERE id = %d", $campaign->campaign_id );
            $result = $wpdb->get_row( $sql );

            if(!$result->enable)
                return $regular_price;

            $discount_value = $result->discount_value;
            if($discount_value > 0){
                $discount_amount = $regular_price * ($discount_value/100);
                $regular_price=$regular_price-$discount_amount;
                $decimals = wc_get_price_decimals();
                $regular_price = $this->wc_cart_round_discount($regular_price, $decimals);
            }

        }

        return $regular_price;
    }

    /**
     * Round discount.
     *
     * @param  float $value
     * @param  int $precision
     * @return float
     */
    public function wc_cart_round_discount( $value, $precision ) {
        if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
            return round( $value, $precision, WC_DISCOUNT_ROUNDING_MODE );
        } else {
            // Fake it in PHP 5.2.
            if ( 2 === WC_DISCOUNT_ROUNDING_MODE && strstr( $value, '.' ) ) {
                $value    = (string) $value;
                $value    = explode( '.', $value );
                $value[1] = substr( $value[1], 0, $precision + 1 );
                $value    = implode( '.', $value );

                if ( substr( $value, -1 ) === '5' ) {
                    $value = substr( $value, 0, -1 ) . '4';
                }
                $value = floatval( $value );
            }
            return round( $value, $precision );
        }
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Multi_Vendor_Campaign_Loader. Orchestrates the hooks of the plugin.
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @param $vendor_id
     * @param $campaign_id
     * @return bool
     */
	public function subscribe($product_id, $vendor_id, $campaign_id, $status = 0) {
		global $wpdb;

		$table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';
		$table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

        $sql = $wpdb->prepare( "SELECT * FROM ". $table_campaign ." WHERE id = %d ", $campaign_id);
		$campaign = $wpdb->get_row( $sql );


		if($campaign && $this->valid_campaign_to_subscribe($campaign) && $this->is_qualified($vendor_id, $product_id, $campaign_id) && !$this->is_subscribed($product_id))
		{
			if(wc_get_product($product_id))
			{
                $date = strftime('%Y-%m-%dT%H:%M:%S', strtotime('now'));
				$result = $wpdb->insert( $table_campaign_product, array( "product_id" => $product_id, "vendor_id" => $vendor_id , "campaign_id" => $campaign_id, "subscription_date" => $date, "status" => $status, "active" => 0 ), array( '%d', '%d', '%d', '%s', "%d", "%d" ) );

				if($result)
				{
					return true;
				}
			}
		}


		return false;
	}

	public function is_subscribed($product_id){
        global $wpdb;
        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
        $sql = $wpdb->prepare( "SELECT * FROM ". $table_campaign_product ." WHERE status != 2 AND product_id = %d ", $product_id);
        $product = $wpdb->get_row( $sql );
        if($product)
            return true;
        return false;
    }

    /**
     * Subscribe Product To Campaign
     */
    public function subscribe_products_to_campaign(){
        if ( ! check_ajax_referer( 'rad-mvc-security-nonce', 'security' ) ) {
            wp_send_json_error( 'Invalid security token sent.' );
            wp_die();
        }

        global $wpdb;
        global $current_user;
        wp_get_current_user();

        // Input data sanitization
        $campaign_id = isset( $_POST['campaign_id'] ) 
            ? intval( $_POST['campaign_id'] )
            : "";

        $product_ids = isset( $_POST['product_ids'] ) 
            ? sanitize_text_field( $_POST['product_ids'] )  
            : "";

        $vendor_id = intval( get_post( explode(",", $product_ids)[0] )->post_author );

        $status = isset( $_POST['status'] ) 
            ? intval( $_POST['status'])
            : 0;

        if($product_ids == "" || $campaign_id == "")
            wp_send_json(0);

        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_campaign_product WHERE product_id in (%s) AND campaign_id = %d AND vendor_id = %d", 
            $product_ids,
            $campaign_id, 
            $vendor_id
        );

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        $message = array();
        if(empty($result)){
            $product_ids = explode(",", $product_ids);
            foreach ($product_ids as $product_id){
                $added = $this->subscribe($product_id,$vendor_id,$campaign_id,$status);
                $message[$product_id] = $added;
            }

        }

        wp_send_json($message);

    }

    public function update_campaign_product_status(){
        if ( ! check_ajax_referer( 'rad-mvc-security-nonce', 'security' ) ) {
            wp_send_json_error( 'Invalid security token sent.' );
            wp_die();
        }
        
        global $wpdb;

        $campaign_product_id = intval( $_POST['campaign_product_id'] );
        $status = 0;

        if($_POST['status'] == "1")
        {
            $status = 1;
        }
        elseif ($_POST['status'] == "2") {
            $status = 2;
        }

        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
        $sql = $wpdb->prepare("UPDATE ". $table_campaign_product ." SET status = %d WHERE id= %d", $status, $campaign_product_id);
        $result = $wpdb->query($sql);
        
        if($result === 1) // 1 row updated
        {
            wp_send_json($status);
        }

        wp_send_json(false);

    }

    /**
     * return number of pending products
     * @return int
     */
    public function get_number_of_pending_products($campaign_id = null) {
        global $wpdb;

        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

        if( is_null($campaign_id))
        {
            $wpdb->get_results( "SELECT id FROM ". $table_campaign_product ." WHERE status = 0" );
        }
        else
        {
            $sql = $wpdb->prepare("SELECT id FROM ". $table_campaign_product ." WHERE status = 0 and campaign_id = %d", $campaign_id);
            $wpdb->get_results($sql);
        }
        
        $numbers = $wpdb->num_rows;

        return $numbers;

    }

    /**
     * return number of pending products
     * @return int
     */
    public function get_title_of_campaign($campaign_id) {
        global $wpdb;

        $table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';

        if( !is_null($campaign_id) && is_int(intval($campaign_id)) )
        {
            $campaign_id = intval($campaign_id);
            $sql = $wpdb->prepare("SELECT title FROM ". $table_campaign ." WHERE id = %d", $campaign_id);
            $ret = $wpdb->get_row($sql);

            if( $ret && isset($ret->title) )
            {
                return $ret->title;
            }
        }
        
        return '';

    }

    /**
     * @param $campaign_id
     * @return array
     */
    public function get_subscribed_products_of_campaign($campaign_id){
        global $wpdb;
        global $current_user;
        wp_get_current_user();
        $vendor_id=$current_user->ID;

        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
        $sql = $wpdb->prepare("SELECT product_id, status FROM ". $table_campaign_product ." WHERE campaign_id = %d AND vendor_id = %d", $campaign_id, $vendor_id);

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        $list = array();
        if($result){
            foreach ($result as $res) {
                $product_id = $res['product_id'];
                $product = new WC_Product($product_id);
                $list[$product_id] = array('title' => $product->get_name(),'img' => $product->get_image(), 'status' => $res['status']);
            }
        }

        return $list;

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Multi_Vendor_Campaign_Loader. Orchestrates the hooks of the plugin.
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @param $venodr_id
     * @param $campaign_id
     * @return bool
     */
	private function unsubscribe($product_id, $venodr_id, $campaign_id) {
		global $wpdb;

		if(get_current_user_id() != $venodr_id)
			return false;

		$table_name = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
		$result = $wpdb->delete( $table_name, array( "product_id" => $product_id, "vendor_id" => $venodr_id, "campaign_id" => $campaign_id), array( '%d', '%d', '%d' ) );
		
		if($result) {
			return true;
		}

		return false;
	}

    /**
     * Retrieve campaigns data from the database
     *
     * @return mixed
     */
    public function get_all_campaigns() {

        global $wpdb;

        $table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';

        $query = $wpdb->prepare("SELECT * FROM $table_campaign WHERE enable=1");
        $result = $wpdb->get_results($query,  'ARRAY_A');

        if($result)
            return $result;

        return null;
    }

    /**
     * Retrieve products of a campaign from the database
     *
     *
     * @param $campaign_id
     * @return null/ array
     */
    public function get_products_of_campaign($campaign_id) {
        global $wpdb;

        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
        $sql = $wpdb->prepare("SELECT product_id FROM ". $table_campaign_product ." WHERE campaign_id = %d", $campaign_id);

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        $array = array();
        if($result){
            foreach ($result as $res){
                $array[] = $res['product_id'];
            }
            return $array;
        }

        return $array;
    }

    /**
     * Check subscription of a product in a campaign and return subscribed campaign_ID.
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return null|object
     */
	public function get_campaign_of_product($product_id) {
		global $wpdb;

		$table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';
		$table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

		$sql = $wpdb->prepare( "SELECT P.id, P.description, P.enable, P.class_name, P.badge, P.badge_text, P.commission_fixed, P.commission_fee, P.commission_percentage, P.activation_date, P.deactivation_date, P.subscribe_start_date, P.subscribe_end_date FROM ". $table_campaign_product ." as PP JOIN ". $table_campaign ." as P On P.id = PP.campaign_id WHERE PP.status = 1 AND PP.product_id = %d", $product_id );

		$campaign = $wpdb->get_row( $sql );

		if($campaign)
			return $campaign;


		return null;
	}


    /**
     * Ajax handler for unsubscription.
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return null|object
     */
    public function unsubscribe_product() {
        if ( ! check_ajax_referer( 'rad-mvc-security-nonce', 'security' ) ) {
            wp_send_json_error( 'Invalid security token sent.' );
            wp_die();
        }

        global $current_user;
        wp_get_current_user();

        $campaign_id    = intval( $_POST['campaign_id'] );
        $product_id     = intval( $_POST['product_id'] );
        $vendor_id      = intval( $current_user->ID );

        $result = $this->unsubscribe($product_id, $vendor_id, $campaign_id);

        wp_send_json($result);

    }

    /**
     * Check subscription of a product in a campaign and return subscribed campaign_ID.
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return null|object
     */
    public function search_products_by_title() {

        if ( ! check_ajax_referer( 'rad-mvc-security-nonce', 'security' ) ) {
            wp_send_json_error( 'Invalid security token sent.' );
            wp_die();
        }

        global $current_user;
        wp_get_current_user();

        $title = sanitize_text_field( $_POST['title'] );
        $campaign_id = intval( $_POST['campaign_id'] );
        $is_administrator = in_array( 'administrator', $current_user->roles );

        $subscribed = $this->get_products_of_campaign($campaign_id);
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            's' => $title,
            'post_status' => 'publish',
            'fields' => 'ids'
        );

        // If user is not administrator, just show their own products
        if ( !$is_administrator ) {
            $args['author__in'] = $current_user->ID;
        }

        $loop = new WP_Query($args);
        $list = array();


        while ($loop->have_posts()) : $loop->the_post();

            if ($this->is_qualified($current_user->ID, get_the_ID(), $campaign_id) && !$this->is_subscribed(get_the_ID())) {
                if (!in_array(get_the_ID(), $subscribed)) {
                    $img = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'thumbnail');
                    if($img && isset($img['0']))
                        $img = $img[0];

                    $id = get_the_ID();

                    if ( $is_administrator ) {
                        $list_item = "<li class='newitem' data-productid='" . get_the_ID() . "'>". get_the_title() ."</li>";
                    } else {
                        $list_item = '<li class="newitem" data-productid="'. $id .'">
                            <span class="img">
                                <img src="' . $img .'">
                            </span>
                            <span class="remove"></span>' . get_the_title() . '
                            <span class="status pending">
                                <span class="rejected">' . esc_html__('Rejected', 'multi_vendor_campaign') . '</span>
                                <span class="approved">' . esc_html__('Approved', 'multi_vendor_campaign') . '</span>
                                <span class="pending">' . esc_html__('Pending…', 'multi_vendor_campaign') . '</span>
                                <span class="loading">
                                    <span class="bounce1"></span>
                                    <span class="bounce2"></span>
                                    <span class="bounce3"></span>
                                </span>
                            </span>
                        </li>';
                    }

                    $list[get_the_ID()] = array('item' => $list_item);
                }

            }
        endwhile;

        wp_reset_query();

        wp_send_json($list);

    }

    /**
     * Check expiration of campaigns
     *
     * @since    1.0.0
     * @access   private
     * @param $product_campaign
     * @return bool
     */
	public function valid_product_campaign($product_campaign) {

		//Check the campaign
		if( is_null( $product_campaign ) || $product_campaign->enable == false)
		{
			return false;
		}

	    $now = strftime('%Y-%m-%dT%H:%M:%S', strtotime('now'));
	    $campaign_begin = strftime('%Y-%m-%dT%H:%M:%S', strtotime($product_campaign->activation_date));
	    $campaign_end = strftime('%Y-%m-%dT%H:%M:%S', strtotime($product_campaign->deactivation_date));

	    // it's out of date
	    if (($now < $campaign_begin) || ($now > $campaign_end))
	    {
	      return false;
	    }

	    return true;
	}

    /**
     * Check subscription time of campaigns
     *
     * @since    1.0.0
     * @access   public
     * @param $campaign
     * @return bool
     */
    public function valid_campaign_to_subscribe($campaign) {

        //Check the campaign
        if(is_array($campaign))
        {
            if( empty( $campaign ))
            {
                return false;
            }

            $campaign_subscribe_begin = strftime('%Y-%m-%dT%H:%M:%S', strtotime($campaign['subscribe_start_date']));
            $campaign_subscribe_end = strftime('%Y-%m-%dT%H:%M:%S', strtotime($campaign['subscribe_end_date']));
        }
        elseif (is_object($campaign))
        {
            if( is_null( $campaign ))
            {
                return false;
            }

            $campaign_subscribe_begin = strftime('%Y-%m-%dT%H:%M:%S', strtotime($campaign->subscribe_start_date));
            $campaign_subscribe_end = strftime('%Y-%m-%dT%H:%M:%S', strtotime($campaign->subscribe_end_date));
        }

        $now = strftime('%Y-%m-%dT%H:%M:%S', strtotime('now'));

        // it's out of date
        if (($now < $campaign_subscribe_begin) || ($now > $campaign_subscribe_end)) {
            return false;
        }

        return true;
    }

    /**
     * Check rules of campaigns(vendor rules)
     *
     * @since    1.0.0
     * @access   public
     * @param $campaign
     * @return bool
     */
    public function is_qualified_to_show($campaign) {

        $ruleType = $campaign['rule_type'];
        $ruleOperator = $campaign['rule_operator'];
        $ruleValue = floatval($campaign['rule_value']);

        $ruleObject = 0 ;


        if($ruleType == 'vendor_sale' || $ruleType == 'vendor_age')
        {
            global $current_user;
            wp_get_current_user();
            $vendor_id=$current_user->ID;

            if ( $ruleType == 'vendor_sale' ) 
            {
                $ruleObject = $this->get_vendor_sale($vendor_id);
            } 
            elseif ( $ruleType == 'vendor_age' ) 
            {
                $ruleObject = $this->get_vendor_age($vendor_id);
            }

            return $this->compare($ruleOperator, $ruleObject, $ruleValue);            
        }

        return true;
    }



    /**
     * Check qualification of vendor/product
     *
     * @since    1.0.0
     * @access   private
     * @param $vendor_id
     * @param $product_id
     * @param $campaign_id
     * @return bool
     */
	private function is_qualified($vendor_id, $product_id, $campaign_id) {
		global $wpdb;

		$table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';

		$sql = $wpdb->prepare( "SELECT * FROM ". $table_campaign ." WHERE id = %d", $campaign_id);
		$campaign = $wpdb->get_row( $sql);

		if($campaign)
		{
			$ruleType = $campaign->rule_type;
			$ruleOperator = $campaign->rule_operator;
			$ruleValue = floatval($campaign->rule_value);

			$ruleObject = 0 ;


            if ( $ruleType == 'no_rule' ) 
            {
                return true;
            }
			elseif ( $ruleType == 'vendor_sale' ) 
            {
				$ruleObject = $this->get_vendor_sale($vendor_id);
			}
			elseif ( $ruleType == 'vendor_age' ) 
            {
				$ruleObject = $this->get_vendor_age($vendor_id);
			}
			elseif ( $ruleType == 'product_sale' ) 
            {
				$ruleObject = $this->get_product_sale($product_id);
			}
			elseif ( $ruleType == 'product_age' ) 
            {
				$ruleObject = $this->get_product_age($product_id);
			}

			return $this->compare($ruleOperator, $ruleObject, $ruleValue);
		}
		return false;
	}

    /**
     * Get approved products with campaign information
     *
     * @since    1.0.0
     * @access   public
     * @return   object
     */
    public function get_approved_products($campaign_position)
    {
        global $wpdb;

        $table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';
        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

        $sql = $wpdb->prepare( "SELECT P.id, PP.product_id, P.campaign_position FROM " . $table_campaign_product . " as PP JOIN " . $table_campaign ." as P On P.id = PP.campaign_id WHERE P.campaign_position = '%s' AND P.enable = 1 AND PP.status = 1", $campaign_position);
        $campaignProducts = $wpdb->get_results($sql);

        return $campaignProducts;
    }

    /**
     * Get approved products and campaign IDs (Active campaigns)
     *
     * @since    1.0.0
     * @access   public
     * @return   object
     */
    public function get_approved_campaign_products()
    {
        global $wpdb;

        $table_campaign = $wpdb->base_prefix . 'rad_mvc_table_campaign';
        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

        $query = $wpdb->prepare(
            "SELECT 
                P.subscribe_start_date,
                P.subscribe_end_date, 
                PP.campaign_id, 
                PP.product_id 
            FROM " . $table_campaign_product . " as PP 
            JOIN " . $table_campaign ." as P 
            On P.id = PP.campaign_id 
            WHERE  P.enable = 1 AND PP.status = 1"
        );
        $campaignProducts = $wpdb->get_results( $query,'ARRAY_A' );

        //Filter active campaigns
        $now = strftime('%Y-%m-%dT%H:%M:%S', strtotime('now'));
        $activeCampaigns = array();
        foreach ($campaignProducts as $campaignProduct) {
            $campaign_subscribe_begin = strftime('%Y-%m-%dT%H:%M:%S', strtotime($campaignProduct['subscribe_start_date']));
            $campaign_subscribe_end = strftime('%Y-%m-%dT%H:%M:%S', strtotime($campaignProduct['subscribe_end_date']));

            if (($now >= $campaign_subscribe_begin) && ($now <= $campaign_subscribe_end)){
                $activeCampaigns[] = $campaignProduct;
            }     
        }

        return $activeCampaigns;
    }
    /**
     * Get amount of sale of vendor
     *
     * @since    1.0.0
     * @access   private
     * @param $vendor_id
     * @return int|null|string
     */
	private function get_vendor_sale($vendor_id) {
		$total_sales = 0;
		//check which multiple multi-vendor plugin is active and get overall sale of the vendor
		if ( class_exists('WCMp') ) { // WC market plugin
			$vendor = get_wcmp_vendor(get_current_vendor_id());
	        $vendor = apply_filters( 'wcmp_dashboard_sale_stats_vendor', $vendor);

            $args = array(
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'post_status' => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded','wc-failed'),
                'meta_query' => array(
                    array(
                        'key' => '_commissions_processed',
                        'value' => 'yes',
                        'compare' => '='
                    )
                ),
            );
				
            $qry = new WP_Query( $args );
				
            $orders = apply_filters('wcmp_filter_orders_report_overview' , $qry->get_posts(),  $vendor->id);
            if ( !empty($orders) ) {
                foreach($orders as $order_obj) {

                    $order = new WC_Order( $order_obj->ID );
                    $vendors_orders = get_wcmp_vendor_orders(array('order_id' => $order->get_id()));
                    if(is_user_wcmp_vendor(get_current_vendor_id())){
                        $vendors_orders_amount = get_wcmp_vendor_order_amount(array('order_id' => $order->get_id()),get_current_vendor_id());

                        $total_sales += $vendors_orders_amount['total'] - $vendors_orders_amount['commission_amount'];
                        $current_vendor_orders = wp_list_filter($vendors_orders, array('vendor_id'=>get_current_vendor_id()));
                        foreach ($current_vendor_orders as $key => $vendor_order) {
                            $item = new WC_Order_Item_Product($vendor_order->order_item_id);
                            $total_sales += $item->get_subtotal();
                        }
                    }
                }
            }


		}
		elseif ( class_exists('WC_Vendors') ) { // WC vendors plugin
            global $wpdb;

            $args = array(
                'post_type' 			=> 'product',
                'post_status' 			=> 'publish',
                'author__in'			=> esc_sql( $vendor_id ),
                'meta_key' 		 		=> 'total_sales',
                'orderby' 		 		=> 'meta_value_num',
                'fields'                =>'ids',
                'tax_query'             => array( 
                    array(
                        'taxonomy'  => 'product_visibility',
                        'terms'     => array( 'exclude-from-catalog' ),
                        'field'     => 'name',
                        'operator'  => 'NOT IN',
                    ) 
                )
            );

            $posts = new WP_Query( $args );
            if (isset($posts->posts)) {
                $ids = $posts->posts;
            }

            if(empty($ids))
            {
                return 0;
            }

            //we now have an array of ids from that category, so then
            $idList = implode(",", $ids); //turn the array into a comma delimited list

            $meta_key = 'total_sales';//set this to your custom field meta key
            $total_sales = $wpdb->get_var($wpdb->prepare("
                                  SELECT sum(meta_value) 
                                  FROM $wpdb->postmeta 
                                  WHERE meta_key = %s 
                                  AND post_id in (%s)", esc_sql($idList), $meta_key));
			
		}
        elseif ( class_exists('WeDevs_Dokan') ) { // Dokan plugin
            $total_sales = dokan_author_total_sales( $vendor_id );
        }
        elseif ( class_exists('YITH_Vendors') ) { // Yith vendors plugin
            $vendor = yith_get_vendor( $vendor_id );
            $order_ids = $vendor->get_orders();
            $amount    = $items_number = 0;
            $allowed_order_statuses = array( 'completed', 'processing' );

            foreach ( $order_ids as $order_id ) {
                $order = wc_get_order( $order_id );
                /**
                 * WC return start date and end date in midnight form.
                 * To compare it with wc order date I need to convert
                 * order date in midnight form too.
                 */ 
                $order_date = $order instanceof WC_Order ? strtotime( 'midnight', strtotime( yit_get_prop( $order, 'order_date' ) ) ) : false;
                if ( $order_date && in_array( $order->get_status(), $allowed_order_statuses ) && $order_date >= $this->start_date && $order_date <= $this->end_date ) {
                    $vendor_product_ids = $vendor->get_products();
                    $order_items        = $order->get_items();
                    foreach ( $order_items as $order_item ) {
                        if ( in_array( $order_item['product_id'], $vendor_product_ids ) ) {

                            if( ! empty( $order_item['line_total'] ) ){
                                /* === Chart Data === */
                                $series = new stdClass();
                                $series->order_date = yit_get_prop( $order, 'date_created' );
                                $series->qty = absint( $order_item['qty'] );
                                $series->line_total = wc_format_decimal( $order_item['line_total'], wc_get_price_decimals() );
                                $report_data['series'][ $vendor->id ][] = $series;
                            }

                            $items_number += $order_item['qty'];
                            $amount += floatval( $order_item['line_total'] );
                        }
                    }
                }
            }
            $total_sales = $amount;
        }

        if(is_null($total_sales) || empty($total_sales))
            return 0;

		return $total_sales;
	}

    /**
     * Get age of vendor
     *
     * @since    1.0.0
     * @access   private
     * @param $vendor_id
     * @return float
     */
	private function get_vendor_age($vendor_id) {
	    $udata = get_userdata( $vendor_id );
		return $this->date_diff_in_year($udata->user_registered, 'now');
	}


    /**
     * get sale amount of a product
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return int|mixed
     */
	private function get_product_sale($product_id) {

		$units_sold = get_post_meta( $product_id, 'total_sales', true );
		if(is_numeric($units_sold))
			return $units_sold;

		return 0;

	}

    /**
     * get age of product
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return float|int
     */
	private function get_product_age($product_id) {

		if( function_exists( 'wc_get_product' ) )
		{
			$product = wc_get_product($product_id);
			if($product)
			{
				$created_date =  $product->get_date_created();
				if( !is_null( $created_date ) )
				{
                    $created_date = str_replace( '/', '-', $created_date.date('') );
                    $created_date = strftime('%Y-%m-%d', strtotime($created_date));

                    return $this->date_diff_in_year($created_date, 'now');
				}

			}
		}

		return 0;
	}


    /**
     * Helper function to compare 2 item based on operation
     *
     * @since    1.0.0
     * @access   private
     * @param $operator
     * @param int $val1
     * @param int $val2
     * @return bool
     */
	private function compare($operator, $val1= 0, $val2 = 0) {
	    $operator=htmlspecialchars_decode($operator);
		if( !is_numeric( $val1 ) || !is_numeric( $val2 ) )
			return false;

		if($operator == '>')
		{
			return ($val1 > $val2) ? true : false;
		}
		elseif ($operator == '>=') {
			return ($val1 >= $val2) ? true : false;
		}
		elseif ($operator == '=') {
			return ($val1 == $val2) ? true : false;
		}
		elseif ($operator == '<') {
			return ($val1 < $val2) ? true : false;
		}
		elseif ($operator == '<=') {
			return ($val1 <= $val2) ? true : false;
		}

		return false;
	}

    /**
     * Helper function to calculate differences between 2 dates
     *
     * @since    1.0.0
     * @access   private
     * @param $first_date
     * @param $second_date
     * @return float
     */
	private function date_diff_in_year($first_date, $second_date) {
		$diff = abs(strtotime($second_date) - strtotime($first_date));
		$years = floor($diff / (365*60*60*24));

		return $years;
	}

    /**
     * Check subscription of a product in a campaign and return badge.
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return null|object
     */
    public function get_badge_status($product_id) {
        $badge=new stdClass;
        $badge->badge='0';

        $product_campaign = $this->get_campaign_of_product($product_id);

        if( $this->valid_product_campaign( $product_campaign ) )
        {
            return $product_campaign;
        }

        return $badge;
    }

    /**
     * Check subscription of a product in a campaign and return class_name.
     *
     * @since    1.0.0
     * @access   private
     * @param $product_id
     * @return null|object
     */
    public function get_class_name($product_id) {
        $class_name=new stdClass;
        $class_name->class_name='0';

        $product_campaign = $this->get_campaign_of_product($product_id);

        if( $this->valid_product_campaign( $product_campaign ) )
        {
            return $product_campaign;
        }

        return $class_name;
    }

    /**
     * Save campaign data in order
     *
     * @since    1.0.0
     * @access   public
     * @param    $order_id
     */
    public function save_order_metadata($order_id) {
        $order       = new WC_Order( $order_id );
        $order_items = $order->get_items();

        $db_campaign_products = $this->get_approved_campaign_products();

        if(!$db_campaign_products || count($db_campaign_products) == 0)
            return;

        //creat an array of product_id=>campaign_id to ease of searching
        $products_campaigns = array();
        foreach ($db_campaign_products as $pp) {
            $products_campaigns[$pp['product_id']] = $pp['campaign_id'];
        }

        $campaign_ids = array();
        foreach ( $order_items as $order_item ) {
            $product_id = $order_item['product_id'];
            if ( isset($products_campaigns[$product_id])) {
                // Sanitize user input  and update the meta field in the database.
                $campaign_ids[] = $products_campaigns[$product_id];
                
            }
        }
        if( count($campaign_ids) > 0 )
        {
            update_post_meta( $order_id, '_rad_mvc_campaign_ids', $campaign_ids );
        }

    }

}
