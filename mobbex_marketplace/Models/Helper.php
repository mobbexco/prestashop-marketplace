<?php

namespace Mobbex\PS\Marketplace\Models;

use \Mobbex\PS\Marketplace\Models\Vendor;
use \Mobbex\PS\Checkout\Models\Logger;
use \Mobbex\PS\Checkout\Models\PriceCalculator;
use \Mobbex\PS\Checkout\Models\CustomFields;

class Helper
{
    const PLUGIN_VERSION = '1.2.2';
    const PS_16 = "1.6";
    const PS_17 = "1.7";

    /**
     * Get cart products considering cart rules
     * 
     * @param object $cart
     * @param array $rules
     * 
     * @return array vendors
     * 
     */
    public static function getCartProducts($cart)
    {
        $priceCalculator = new PriceCalculator($cart);

        // Check if there is any cart rule. Applies cart rules if appropriate.
        return self::getProductsVendors(
            $cart->getCartRules() ? $priceCalculator->getCartRules() : $cart->getProducts(true)
        );
    }

    /**
     * Get the vendors of a list of products.
     * 
     * @param array $products
     * 
     * @return array $vendors
     * 
     */
    public static function getProductsVendors($products)
    {
        $vendors = [];

        foreach ($products as $product) {
            $vendor_id = self::getVendorFromProdId($product['id_product']);

            if (!$vendor_id || !Vendor::getVendors(true, 'id', $vendor_id)) {
                Logger::log('error', 'Trying to split payment on products without vendor', $product['id_product'], $vendor_id);
                continue;
            }

            $vendors[$vendor_id][] = $product;
        }

        return $vendors;
    }

    /**
     * Get a vendor id from product id
     * 
     * @param string $id
     * 
     * @return string|bool
     * 
     */
    public static function getVendorFromProdId($id)
    {
        $vendor_id = CustomFields::getCustomField($id, 'product', 'vendor');
            
        //If a product did not have vendor try to obtain it by category
        if(!$vendor_id || !Vendor::getVendors(true, 'id', $vendor_id))
            $vendor_id = self::getCategoryVendors($id);

        return $vendor_id;
    }

    /**
     * Get the the vendor id from the product category
     * 
     * @param string $product_id
     * 
     * @return string|bool 
     * 
     */
    public static function getCategoryVendors($product_id)
    {
        $product = new \Product($product_id);

        foreach ($product->getCategories() as $cat) {
            $vendor_id = CustomFields::getCustomField($cat, 'category', 'vendor');

            if($vendor_id)
                return $vendor_id;
        }

        return false;
    }

    /**
     * Get the fee for a especific product.
     * @param string $productId
     * @return string
     */
    public static function getProductFee($productId)
    {
        //Get Product fee
        $fee = CustomFields::getCustomfield($productId, 'product', 'fee');
        
        if ($fee)
            return $fee;

        //get category fee
        $product = new \Product($productId);
        foreach ($product->getCategories() as $categoryId) {
            $fee = CustomFields::getCustomField($categoryId, 'category', 'fee');
            if ($fee)
                return $fee;
        }

        //Get Vendor fee
        $vendorId = self::getVendorFromProdId($productId);
        if ($vendorId) {
            $vendor = Vendor::getVendors(true, 'id', $vendorId);
            if ($vendor['fee'])
                return $vendor['fee'];
        }

        //return general fee
        return \Configuration::get("MOBBEX_MARKETPLACE_FEE");
    }

    public static function calculateFee($product_id, $total)
    {
        $fee = self::getProductFee($product_id);
        $feePercentage = str_replace('%', '', (string) $fee);

        if (!$fee)
            return 0;

        // If the fee is fixed, return it
        if (is_numeric($fee))
            return (float) $fee;

        return is_numeric($feePercentage) ? $total * $feePercentage / 100 : 0;
    }

    public static function getMarketplaceItems($products, $cart_total, $mobbex_total, $op_type)
    {
        $items = [];
        foreach ($products as $product) {

            $vendor_id = CustomFields::getCustomField($product['id_product'], 'product', 'vendor');
            $vendor    = Vendor::getVendors(true, 'id', $vendor_id);
            $dif       = ($cart_total / $mobbex_total * 100) - 100;
            $dif       = $dif <= 9 ? '0.0' . $dif : '0.' . $dif;
            
            $items[$product['id_product']]['name']          = $product['name'];
            $items[$product['id_product']]['quantity']      = $product['quantity'];
            $items[$product['id_product']]['total']         = $product['total_wt'] + ($product['total_wt'] * $dif);
            $items[$product['id_product']]['vendor_name']   = isset($vendor['name']) ? $vendor['name'] : '';

            if($op_type === "payment.split-hybrid"){
                $items[$product['id_product']]['fee_amount']    = self::getProductFee($product['id_product']);
                $items[$product['id_product']]['fee']           = self::calculateFee($product['id_product'], $product['total_wt']);
                $items[$product['id_product']]['vendor_hold']   = isset($vendor['hold']) && $vendor['hold'] == 1 ? 'YES' : 'NO';
            }

        }
        return $items;
    }
}