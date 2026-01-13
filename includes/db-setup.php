<?php
// includes/db-setup.php

if (!defined('WPINC')) die;

function monday_integration_create_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'monday_leads_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        source varchar(100) DEFAULT '' NOT NULL,
        status varchar(50) DEFAULT '' NOT NULL,
        response_body text NOT NULL,
        full_payload longtext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
