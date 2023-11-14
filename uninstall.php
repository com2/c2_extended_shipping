<?php
// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

$option_name = 'woocommerce_c2_extended_shipping_settings';

delete_option($option_name);

/** When needed **/
// for site options in Multisite
//delete_site_option( $option_name );

// drop a custom database table
//global $wpdb;
//$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mytable" );
