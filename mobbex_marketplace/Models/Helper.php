<?php

namespace Mobbex\PS\Marketplace\Models;

class Helper
{
    const PLUGIN_VERSION = '1.1.0';
    const PS_16 = "1.6";
    const PS_17 = "1.7";

    /**
     * Get the vendors of a list of products.
     * @param array
     * @return array
     */
    public static function getProductsVendors($products)
    {
        $vendors = [];

        foreach ($products as $product) {

            $vendor_id = \Mobbex\PS\Checkout\Models\CustomFields::getCustomField($product['id_product'], 'product', 'vendor');
            
            //If a product did not have vendor or vendor doesnt exist in db stop the process
            if(!$vendor_id || !\Mobbex\PS\Marketplace\Models\Vendor::getVendors(true, 'id', $vendor_id))
                return false;

            $vendors[$vendor_id][] = $product;
        }

        return $vendors;
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
        $vendorId = \Mobbex\PS\Checkout\Models\CustomFields::getCustomField($productId, 'product', 'vendor');
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
            $items[$product['id_product']]['total']         = round($product['price_wt'] + ($product['price_wt'] * $dif), 2);
            $items[$product['id_product']]['vendor_name']   = isset($vendor['name']) ? $vendor['name'] : '';
            $items[$product['id_product']]['vendor_tax_id'] = isset($vendor['tax_id']) ? $vendor['tax_id'] : '';

            if($op_type === "payment.split-hybrid"){
                $items[$product['id_product']]['fee_amount']    = $fee;
                $fee                                            = $fee <= 9 ? '0.0' . $fee : '0.' . $fee;
                $items[$product['id_product']]['fee']           = round(($product['price_wt'] + ($product['price_wt'] * $dif)) * $fee, 2);
                $items[$product['id_product']]['vendor_hold']   = isset($vendor['hold']) && $vendor['hold'] == 1 ? 'YES' : 'NO';
            }

        }
        return $items;
    }
}
