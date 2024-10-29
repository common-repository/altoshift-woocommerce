<?php

defined( 'ABSPATH' ) or die();

return array(
    array(
        'title' => __( 'Analytics Settings', 'woocommerce-altoshift' ),
        'type'  => 'title',
        'desc'  => '',
        'id'    => 'altoshift_analytics_settings',
    ),

    array(
        'title'   => __( 'Send checkout stats to Altoshift to measure conversions', 'woocommerce-altoshift' ),
        'desc'    => '',
        'id'      => 'altoshift_send_checkout_stats',
        'type'    => 'checkbox',
        'default' => 'yes',
    ),

    array(
        'type' => 'sectionend',
        'id'   => 'altoshift_analytics_settings',
    ),
);
