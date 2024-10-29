<?php

defined( 'ABSPATH' ) or die();

return array(
    array(
        'title' => __( 'Feed Options', 'woocommerce-altoshift' ),
        'type'  => 'title',
        'desc'  => '',
        'id'    => 'altoshift_feed_options',
    ),

    array(
        'title'   => __( 'Protect feed with password', 'woocommerce-altoshift' ),
        'desc'    => '',
        'id'      => 'altoshift_feed_password_protected',
        'type'    => 'checkbox',
        'default' => 'no',
    ),

    array(
        'title'   => __( 'Feed password', 'woocommerce-altoshift' ),
        'desc'    => '',
        'id'      => 'altoshift_feed_password',
        'type'    => 'text',
        'css'     => 'width: 100%',
        'default' => '',
    ),

    array(
        'title'   => __( 'Export product prices', 'woocommerce-altoshift' ),
        'desc'    => '',
        'id'      => 'altoshift_feed_price_export',
        'type'    => 'checkbox',
        'default' => 'yes',
    ),

    array(
        'type' => 'sectionend',
        'id'   => 'altoshift_feed_options',
    ),
);
