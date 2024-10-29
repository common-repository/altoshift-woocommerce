<?php

/*
Plugin Name:  Altoshift Woocommerce Plugin
Description:  Plugin for Altoshift search integration into your Woocommerce shop
Version:      1.0.4
Author:       Altoshift
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/


namespace Altoshift\Woocommerce;

defined('ABSPATH') or die;


// this plugin cannot work without woocommerce plugin
if (isWooCommercePluginActive()) {

    class AltoshiftWoocommercePlugin
    {
        const STATS_ENDPOINT = 'https://api.altoshift.com/statsendpoint/stats';
        private static $_instance = null;
        private $_viewsDir = null;
        private $_pluginDir = null;

        public function __construct()
        {
            $this->_pluginDir = plugin_dir_path(__FILE__);
            $this->_viewsDir = $this->getPluginDir() . 'includes/views/';
            require $this->getPluginDir() . 'includes/classes/autoload.php';

            new \Altoshift\Woocommerce\Admin();
            new \Altoshift\Woocommerce\Frontend();

            $className = __CLASS__;

            add_action('init', function () use ($className) {
                call_user_func(array($className, 'registerCustomUrls'));
            });
        }

        public static function registerCustomUrls()
        {
            \Altoshift\Woocommerce\Feed\Feed::registerUrls();
        }

        public static function getInstance()
        {
            if (self::$_instance === null) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        public function getViewsDir()
        {
            return $this->_viewsDir;
        }

        public function getPluginDir()
        {
            return $this->_pluginDir;
        }

        public static function postStats($data) {
            wp_remote_post(self::STATS_ENDPOINT, array('body' => $data, 'blocking' => false));
        }

        public static function onPluginEnabled()
        {
            try {
                $pluginData = get_plugin_data(__FILE__);
                self::postStats(array(
                	'event' => 'pluginInstall',
                	'data' => array(
                		'pluginVersion' => $pluginData['Version'],
                		'host' => get_site_url(),
                		'ip' => $_SERVER['SERVER_ADDR'],
                		'locale' => get_locale(),
                		),
                ));
            } catch (Exception $e) {
            }
            self::getInstance()->registerCustomUrls();
            flush_rewrite_rules();
        }

        public static function onPluginDisabled()
        {
            flush_rewrite_rules();
        }

        public static function getClickedProductsFromCookies($engineToken)
        {
            $key = 'als-' . $engineToken;
            if (!isset($_COOKIE[$key])) {
                return array();
            }

            return json_decode(stripslashes($_COOKIE[$key]), true);
        }

        public static function onCheckout($order)
        {
            try {
                $sendStats = get_option('altoshift_send_checkout_stats', 'yes');
                if ($sendStats === 'yes') {
                    $engineToken = get_option('altoshift_engine_token', '');
                    $clicksData = self::getClickedProductsFromCookies($engineToken);
                    if (!count($clicksData)) {
                        // no clicks data - no point to proceed
                        return;
                    }

                    $orderData = $order->get_data();

                    $cartProductIds = array();
                    foreach ($orderData['line_items'] as $product) {
                        $cartProductIds[] = strval($product->get_product_id());
                    }

                    $checkoutStatsPayload = array();
                    $sessionId = $clicksData['sessionId'];
                    foreach ($clicksData['searchProducts'] as $product) {
                        if (in_array($product['productId'], $cartProductIds)) {
                            $checkoutStatsPayload[] = array(
                                'searchId' => $product['searchId'],
                                'productId' => $product['productId'],
                                'sessionId' => $sessionId,
                            );
                        }
                    }

                    if (!count($checkoutStatsPayload)) {
                        // there is no data to send
                        return;
                    }

                    self::postStats(array(
                        'event' => 'checkout',
                        'data' => array(
                            'engineToken' => $engineToken,
                            'userAgent' => wc_get_user_agent(),
                            'products' => $checkoutStatsPayload,
                        ),
                    ));
                }
            } catch (Exception $e) {

            }
        }

        public static function addProductIdMetaTag()
        {
            $post = get_post();
            if ($post !== null && $post->post_type === 'product')
            {
                echo "<meta name=\"productId\" content=\"$post->ID\" />";
            }
        }
    }

    register_activation_hook(__FILE__, array('\Altoshift\Woocommerce\AltoshiftWoocommercePlugin', 'onPluginEnabled'));
    register_deactivation_hook(__FILE__, array('\Altoshift\Woocommerce\AltoshiftWoocommercePlugin', 'onPluginDisabled'));

    add_action('plugins_loaded', array('\Altoshift\Woocommerce\AltoshiftWoocommercePlugin', 'getInstance'), 0);
    add_action('woocommerce_checkout_create_order', array('\Altoshift\Woocommerce\AltoshiftWoocommercePlugin', 'onCheckout'));
    add_action('wp_head', array('\Altoshift\Woocommerce\AltoshiftWoocommercePlugin', 'addProductIdMetaTag'));
}


function isWooCommercePluginActive()
{
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ||
        array_key_exists('woocommerce/woocommerce.php', apply_filters('active_sitewide_plugins', get_site_option('active_sitewide_plugins')));
}
