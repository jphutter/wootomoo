<?php

// This file runs when the plugin in uninstalled (deleted).

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

include_once 'config.php';

// these get removed only when plugin is deleted

delete_option( WOOTOMOO_URL_KEY );
delete_option( WOOTOMOO_TOKEN_KEY );
delete_option( WOOTOMOO_CAT_LIST );

global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wootomoo_links');

