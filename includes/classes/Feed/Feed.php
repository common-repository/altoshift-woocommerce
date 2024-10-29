<?php

namespace Altoshift\Woocommerce\Feed;

defined('ABSPATH') or die;


class Feed
{
    private static $_instance = null;

    private $_settings = [];

    public $header = [];
    public $products = [];
    public $categories = [];

    public static $predefinedFields = array(
        "id"
    );

    public $allFields = array();

    public static function registerUrls()
    {
        add_feed('altoshift', function () {
            \Altoshift\Woocommerce\Feed\Feed::getInstance()
                ->init()
                ->generate()
                ->render();
        });
    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function fetchSettings()
    {
        $this->_settings = array_merge($this->_settings, array(
            'altoshift_feed_price_export' => get_option('altoshift_feed_price_export', 'no'),
            'altoshift_feed_password_protected' => get_option('altoshift_feed_password_protected', 'no'),
            'altoshift_feed_password' => get_option('altoshift_feed_password', ''),
        ));
    }

    public function init()
    {
        $this->fetchSettings();
        return $this;
    }

    public function generate()
    {
        if (!$this->isAuthorized()) {
            return $this;
        }

        $this->header['title'] = get_bloginfo('name');
        $this->header['link'] = get_site_url();
        $this->header['description'] = sanitize_text_field(get_bloginfo('description'));

        $this->loadProducts();
		if (isset($_GET['skip']) && $_GET['skip'] == 0) {
        		$this->loadCategories();
		}
        return $this;
    }

    public function render()
    {
        if (!$this->isAuthorized()) {
            return $this;
        }

        header('Content-Type: application/xml');

        echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>';
        foreach ($this->header as $headerName => $headerValue) {
            $this->createElement($headerName, $headerValue);
        }

        echo '<allFields>';
        foreach($this->allFields as $field)
        {
            $this->createElement('field', $field);
        }
        echo '</allFields>';

        foreach($this->categories as $category)
        {
            echo '<category>';
            $this->createElement('id', $category->term_id);
            $this->createElement('name', $category->name);
            $this->createElement('description', $category->description);
            $this->createElement('parent', $category->parent);
            $this->createElement('count', $category->count);
            echo '</category>';
        }

        foreach ($this->products as $product) {
            echo '<item>';
            foreach ($product as $field => $value) {
                if ($field == 'categoryIds' || $field === 'categoryTree') {
                    $this->renderProductCategories($value, $field);
                    continue;
                }
                $this->createElement($field, $value);
            }
            echo '</item>';
        }

        echo '</channel></rss>';


        return $this;
    }

    private function renderProductCategories($categories, $element = 'categoryIds')
    {
        echo '<'.$element.'>';
        foreach ($categories as $category)
        {
            $this->createElement('category', $category);
        }
        echo '</'.$element.'>';
    }

    private function loadCategories()
    {
        global $wp_version;
        $productCategories = [];
        if (version_compare($wp_version, '4.5.0', '>=')) {
            $args = array(
                'taxonomy'   => "product_cat",
                'hide_empty' => 0,
            );
            $productCategories = get_terms($args);
        } else {
            $args = array(
                'hide_empty' => 0,
            );

            $productCategories = get_terms( 'product_cat', $args );
        }

        $this->categories = $productCategories;

        return $productCategories;
    }

    private function loadProducts()
    {
        global $woocommerce;

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'cache_results' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'orderby' => 'ID',
            'order' => 'ASC',
            'posts_per_page' => -1
        );
		if (isset($_GET['lastUpdate']) && !empty($_GET['lastUpdate'])) {
			$args['date_query'] = array(
				'column'  => 'post_modified',
				'after'   => '- '.$_GET['lastUpdate'].' days'
			);
		};

        if (version_compare($woocommerce->version, '3.0.0', '>=')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field' => 'name',
                    'terms' => array('exclude-from-search'),
                    'operator' => 'NOT IN',
                ),
            );
        } else {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_visibility',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_visibility',
                    'value' => array('search', 'visible'),
                    'compare' => 'IN',
                ),
                array(
                    'key' => '_visibility',
                    'value' => '',
                ),
            );
        }

        if (isset($_GET['limit']) && !empty($_GET['limit'])) {
            $args['posts_per_page'] = $_GET['limit'];
        }
        if (isset($_GET['skip']) && !empty($_GET['skip'])) {
            $args['offset'] = $_GET['skip'];
        }

        $moreFields = array();
        if (isset($_GET['more_fields']) && !empty($_GET['more_fields'])) {
            try {
                $moreFields = explode(",", $_GET['more_fields']);
            } catch(Exception $e) {
                $moreFields = array();
            }
        }
        $query = new \WP_Query($args);

        foreach ($query->posts as $post) {
            $product = WC()->product_factory->get_product($post->ID);

            $productData = $this->productToArray($post, $product);
            try {
                if (!count($this->allFields)) {
                    $this->allFields = $this->getAdditionalFields($productData);
                }
            } catch (Exception $e) {

            }

            $productItem = array(
                'id' => $productData['id'],
            );

            foreach($moreFields as $field) {
                if (!isset($productData[$field])) {
                    continue;
                }
                $productItem[$field] = $productData[$field];
            }

            $this->products[] = $productItem;
        }
    }

    private function getProductCategoryTree($productId)
    {
        $categoryIds = wc_get_product_cat_ids($productId);

        return $categoryIds;
    }

    private function getProductCategories($productId)
    {
        $ids = array();
        $terms = get_the_terms($productId, 'product_cat');
        foreach ($terms as $term) {
            $ids[] = $term->term_id;
        }

        return $ids;
    }

    public function isProtected()
    {
        return $this->_settings['altoshift_feed_password_protected'] === 'yes' && strlen($this->_settings['altoshift_feed_password']) > 0;
    }

    public function isAuthorized()
    {
        return !$this->isProtected() || $this->_settings['altoshift_feed_password'] === $_GET['secret'];
    }

    private function createElement($name, $value)
    {
        echo '<' . $name . '>';
        $this->wrapCdata($value);
        echo '</' . $name . '>';
    }

    private function wrapCdata($value)
    {
        echo "<![CDATA[$value]]>";
    }

    private function productToArray($post, $product) {
        $imageId = get_post_thumbnail_id($post->ID);
        $imageLinks = wp_get_attachment_image_src($imageId, 'full');
        $productCategoriesTree = $this->getProductCategoryTree($post->ID);
        $productCategories = $this->getProductCategories($post->ID);

        $data = $this->accessProtected($product, "data");
        $data = array_merge($data, array(
            'id' => $post->ID,
            'link' => get_permalink($post),
            'title' => $product->name,
            'description' => $product->description,
            'image_link' => is_array($imageLinks) && count($imageLinks) ? $imageLinks[0] : '',
            'availability' => $product->is_purchasable() && $product->is_in_stock() ? 'in stock' : 'out of stock',
            'price' => $product->regular_price,
            'sale_price' => $product->sale_price,
            'categoryTree' => $productCategoriesTree,
            'categoryIds' => $productCategories,
        ));

        unset($data['name']);
        unset($data['regular_price']);

        return $data;
    }

    private function accessProtected($obj, $prop) {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    private function getAdditionalFields($productData) {
        $productFields = array_keys($productData);
        return array_diff($productFields, self::$predefinedFields);
    }
}