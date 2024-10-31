<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Multi_Vendor_Campaign
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function multi_vendor_campaign_uninstall(){
    global $wpdb;

    // define local private attribute
    $wpdb->multi_vendor_campaign_campaigns = $wpdb->base_prefix . 'rad_mvc_table_campaign';
    $wpdb->multi_vendor_campaign_campaign_products = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

    // Delete option from options table
    delete_option( 'multi-vendor-campaign-db-ver' );

    //remove any additional options and custom table
    $sql = "DROP TABLE IF EXISTS `" . $wpdb->multi_vendor_campaign_campaigns . "`";
    $wpdb->query( $sql );
    $sql = "DROP TABLE IF EXISTS `" . $wpdb->multi_vendor_campaign_campaign_products . "`";
    $wpdb->query( $sql );
}



if ( ! is_multisite() ) {
    multi_vendor_campaign_uninstall();
}
else {
    global $wpdb;
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
    $original_blog_id = get_current_blog_id();

    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        multi_vendor_campaign_uninstall();
    }

    switch_to_blog( $original_blog_id );
}