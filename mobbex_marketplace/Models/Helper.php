<?php

namespace Mobbex\PS\Marketplace\Models;

class Helper
{
    const PLUGIN_VERSION = '1.1.1';
    const PS_16 = "1.6";
    const PS_17 = "1.7";

    /**
     * Get cart products considering cart rules
     * 
     * @param array $cart
     * @param array $rules
     * 
     * @return array $vendors
     * 
     */
    public static function getCartProducts($cart)
    {   
        $vendors = [];
        // Check if there is any cart rule
        if ($cart->getCartRules()) {
            // Applies rules to the corresponding product(s) and get vendors
            $ruleProducts = \Mobbex\PS\Checkout\Models\CartRules::getRules($cart->getCartRules(), $cart->getProducts(true));
            $vendors      = self::getProductsVendors($ruleProducts);
        }
        else {
            // Get vendors from cart products
            $vendors = self::getProductsVendors($cart->getProducts(true));
        }
        return $vendors;
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
            
            //If a product did not have vendor stop the process
            if(!$vendor_id || !\Mobbex\PS\Marketplace\Models\Vendor::getVendors(true, 'id', $vendor_id))
                return;

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
        $vendor_id = \Mobbex\PS\Checkout\Models\CustomFields::getCustomField($id, 'product', 'vendor');
            
        //If a product did not have vendor try to obtain it by category
        if(!$vendor_id || !\Mobbex\PS\Marketplace\Models\Vendor::getVendors(true, 'id', $vendor_id))
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
            $vendor_id = \Mobbex\PS\Checkout\Models\CustomFields::getCustomField($cat, 'category', 'vendor');

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
        $fee = \Mobbex\PS\Checkout\Models\CustomFields::getCustomfield($productId, 'product', 'fee');
        
        if ($fee)
            return $fee;

        //get category fee
        $product = new \Product($productId);
        foreach ($product->getCategories() as $categoryId) {
            $fee = \Mobbex\PS\Checkout\Models\CustomFields::getCustomField($categoryId, 'category', 'fee');
            if ($fee)
                return $fee;
        }

        //Get Vendor fee
        $vendorId = self::getVendorFromProdId($productId);
        if ($vendorId) {
            $vendor = \Mobbex\PS\Marketplace\Models\Vendor::getVendors(true, 'id', $vendorId);
            if ($vendor['fee'])
                return $vendor['fee'];
        }

        //return general fee
        return \Configuration::get("MOBBEX_MARKETPLACE_FEE");
    }

    public static function getMarketplaceItems($products, $cart_total, $mobbex_total, $op_type)
    {
        $items = [];
        foreach ($products as $product) {

            $vendor_id = \Mobbex\PS\Checkout\Models\CustomFields::getCustomField($product['id_product'], 'product', 'vendor');
            $vendor    = \Mobbex\PS\Marketplace\Models\Vendor::getVendors(true, 'id', $vendor_id);
            $fee       = \Mobbex\PS\Marketplace\Models\Helper::getProductFee($product['id_product']);
            $dif       = ($cart_total / $mobbex_total * 100) - 100;
            $dif       = $dif <= 9 ? '0.0' . $dif : '0.' . $dif;
            
            $items[$product['id_product']]['name']          = $product['name'];
            $items[$product['id_product']]['quantity']      = $product['quantity'];
            $items[$product['id_product']]['total']         = $product['total_wt'] + ($product['total_wt'] * $dif);
            $items[$product['id_product']]['vendor_name']   = isset($vendor['name']) ? $vendor['name'] : '';
            $items[$product['id_product']]['vendor_tax_id'] = isset($vendor['tax_id']) ? $vendor['tax_id'] : '';

            if($op_type === "payment.split-hybrid"){
                $items[$product['id_product']]['fee_amount']    = $fee;
                $fee                                            = $fee <= 9 ? '0.0' . $fee : '0.' . $fee;
                $items[$product['id_product']]['fee']           = ($product['total_wt'] + ($product['total_wt'] * $dif)) * $fee;
                $items[$product['id_product']]['vendor_hold']   = isset($vendor['hold']) && $vendor['hold'] == 1 ? 'YES' : 'NO';
            }

        }
        return $items;
    }
}