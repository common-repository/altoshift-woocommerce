<?php

defined( 'ABSPATH' ) or die();

return array(
    array(
        'title' => __( 'Settings', 'woocommerce-altoshift' ),
        'type'  => 'title',
        'desc'  => '',
        'id'    => 'altoshift_settings',
    ),

    array(
        'title'   => __( 'Engine token', 'woocommerce-altoshift' ),
        'desc'    => '',
        'id'      => 'altoshift_engine_token',
        'type'    => 'text',
    ),

    array(
        'type' => 'sectionend',
        'id'   => 'altoshift_settings',
    ),
);
