<?php

namespace Altoshift\Woocommerce;

defined( 'ABSPATH' ) or die;

use Altoshift\Woocommerce\Page\Settings;

class Admin {
    public function __construct() {
        $this->addSettingsPage();
    }

    private function addSettingsPage() {
        add_filter('woocommerce_get_settings_pages', function($settings) {
            $settings[] = new Settings();
            return $settings;
        } );
    }
}