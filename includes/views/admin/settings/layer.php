<?php

defined('ABSPATH') or die;

return array(
    array(
        'title' => __('Layer Options', 'woocommerce-altoshift'),
        'type' => 'altoshift_layer_enabled_title',
        'desc' => '',
        'id' => 'layer_options',
    ),

    array(
        'title' => __('Enable the Layer', 'woocommerce-altoshift'),
        'desc' => '',
        'id' => 'altoshift_layer_enabled',
        'type' => 'checkbox',
        'default' => 'no',
    ),

    array(
        'title' => __('Layer Javascript Code', 'woocommerce-altoshift'),
        'desc' => __('Paste here the Javascript code you will find in your Altoshift Client Panel'),
        'id' => 'altoshift_layer_code',
        'css' => 'margin-top: 5px; width: 100%; height: 500px; font-family: Consolas,Monaco,monospace;',
        'type' => 'textarea',
        'default' => '',
    ),

    array(
        'type' => 'sectionend',
        'id' => 'altoshift_layer_options',
    ),
);
