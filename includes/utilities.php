<?php

/**
 * The utilities and helper functions
 *
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/includes
 */

function rad_mvc_is_dokan_vendor_dashboard($current_page_id) {
    $dashboard_page_id = null;
    $options = get_option( 'dokan_pages' );

    if ( isset( $options['dashboard'] ) && !empty( $options['dashboard'] ) ) {
        $dashboard_page_id = $options['dashboard'];

        if($dashboard_page_id == $current_page_id) {
            return true;
        }
    }

    return false;
}

/**
 * Helper function to get meta for an order.
 *
 * @param \WC_Order $order the order object
 * @param string $key the meta key
 * @param bool $single whether to get the meta as a single item. Defaults to `true`
 * @param string $context if 'view' then the value will be filtered
 * @return mixed the order property
 */
function rad_mvc_get_order_meta( $order, $key = '', $single = true, $context = 'edit' ) {

    // WooCommerce > 3.0
    if ( defined( 'WC_VERSION' ) && WC_VERSION && version_compare( WC_VERSION, '3.0', '>=' ) ) {

        $value = $order->get_meta( $key, $single, $context );

    } else {

        // have the $order->get_id() check here just in case the WC_VERSION isn't defined correctly
        $order_id = is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id;
        $value    = get_post_meta( $order_id, $key, $single );
    }

    return $value;
}
