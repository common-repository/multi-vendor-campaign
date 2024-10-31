<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/includes
 * @author     Your Name <email@example.com>
 */
class Multi_Vendor_Campaign_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */

	/**
	 * Define the function that fired when user activate the plugin.
	 *
	 * Call update method to check need of update process. If plugin runs for the first time create the database.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( self::update() ) {
			return false;
		}

		self::create_database();

    }

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function create_database() {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $campaign_table_name = $wpdb->base_prefix . 'rad_mvc_table_campaign';
        $campaign_product_table_name = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

        // number_of_vendors : not used
        $sql = "CREATE TABLE $campaign_table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            description text NOT NULL,
            enable BIT DEFAULT 1,
            commission_fixed FLOAT NOT NULL,
            commission_fee FLOAT NOT NULL,
            commission_percentage FLOAT NOT NULL,
            discount_value FLOAT NOT NULL,
            activation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            deactivation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            subscribe_start_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            subscribe_end_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            number_of_vendors INT NOT NULL,
            number_of_vendor_products INT NOT NULL,
            out_of_stack BIT DEFAULT 0, 
            campaign_position tinytext NOT NULL,
            rule_type text NOT NULL,
            rule_operator text NOT NULL,
            rule_value FLOAT NOT NULL,
            thumbnail text NOT NULL,
            badge TINYINT DEFAULT 0,
            badge_text text NULL,
            class_name text NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql );

        // active : not used
        $sql = "CREATE TABLE $campaign_product_table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            vendor_id bigint(20) UNSIGNED NOT NULL,
            subscription_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            active BIT DEFAULT 0,
            status TINYINT DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql );
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function update() {
		$current_version = get_option( "multi-vendor-campaign-db-ver", null );

		if ( is_null( $current_version ) ) { // the first installation
			update_option( "multi-vendor-campaign-db-ver", MULTI_VENDOR_CAMPAIGN_VERSION );
			return false;
		}

		if ( version_compare( MULTI_VENDOR_CAMPAIGN_VERSION, $current_version, 'gt' ) ) {
			self::upgrade_data();
		}
		return true;
	}

	/**
	 * Short Description. (use period)
	 *
	 * upgrade database and data.
	 *
	 * @since    1.0.0
	 */
	public static function upgrade_data() {
        update_option( "multi-vendor-campaign-db-ver", MULTI_VENDOR_CAMPAIGN_VERSION );
	}


}
