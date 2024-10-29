<?php

namespace Altoshift\Woocommerce\Page;

defined( 'ABSPATH' ) or die;

use Altoshift\Woocommerce\AltoshiftWoocommercePlugin;

class Settings extends \WC_Settings_Page {
    public function __construct() {
        $this->id     = 'altoshift';
        $this->label  = __('Altoshift', 'woocommerce-altoshift');

        // Register settings Tab
        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );

        // Output sections header and section settings
        add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'before_settings' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );

        // Custom overrides for settings saving to fix unwanted WC behavior
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
    }

    public function get_sections() {
        $sections = array(
            ''           => __( 'Altoshift Layer', 'woocommerce-altoshift' ),
            'feed'  => __( 'Data Feed', 'woocommerce-altoshift' ),
            'analytics-settings'  => __( 'Analytics Settings', 'woocommerce-altoshift' ),
            'settings'  => __( 'Settings', 'woocommerce-altoshift' ),
        );

        return $sections;
    }

    public function get_settings() {
        global $current_section;

        switch ($current_section) {
            case '':
                return include AltoshiftWoocommercePlugin::getInstance()->getViewsDir() . 'admin/settings/layer.php';

            case 'feed':
                return include AltoshiftWoocommercePlugin::getInstance()->getViewsDir() . 'admin/settings/feed.php';

            case 'analytics-settings':
                return include AltoshiftWoocommercePlugin::getInstance()->getViewsDir() . 'admin/settings/analytics-settings.php';

            case 'settings':
                return include AltoshiftWoocommercePlugin::getInstance()->getViewsDir() . 'admin/settings/settings.php';
        }
    }

    public function save() {
        parent::save();

        if (isset($_POST['altoshift_layer_code'])) {
            update_option('altoshift_layer_code', $_POST['altoshift_layer_code']);
        }
    }

    public function before_settings() {
        global $current_section;

        if ($current_section == 'feed') {
            include AltoshiftWoocommercePlugin::getInstance()->getViewsDir() . 'admin/settings/feed-url.php';
        }
    }
}