<?php

class MarketplaceHelper
{
    const MOBBEX_MARKETPLACE_VERSION = '1.0.0';
    const PS_16 = "1.6";
    const PS_17 = "1.7";

    const K_ACTIVE        = "MOBBEX_MARKETPLACE_ACTIVE";
    const K_FEE           = "MOBBEX_MARKETPLACE_FEE";

    /**
     * Get the vendors from a list of products.
     * @param array
     * @return array
     */
    public static function getProductVendors($products, $filter = null)
    {
        $vendors = [];

        foreach ($products as $product) {
            $vendor = MobbexCustomFields::getCustomField($product['id_product'], 'product', 'vendor');
            $vendor = MobbexVendor::getVendors(true, 'id', $vendor);
            $vendor_id = $vendor[0]['id'] ?: '';

            if (empty($vendor_id))
                return [];

            if ($filter)
                array_push($vendors[$vendor_id]['items'], $product);
            else
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
        if (MobbexCustomFields::getCustomfield($productId, 'product', 'fee'))
            return MobbexCustomFields::getCustomfield($productId, 'product', 'fee');

        //get category fee
        $product = new Product($productId);
        foreach ($product->getCategories() as $categoryId) {
            $fee = MobbexCustomFields::getCustomField($categoryId, 'category', 'fee');
            if ($fee)
                return $fee;
        }

        //Get Vendor fee
        if (MobbexCustomFields::getCustomField($productId, 'product', 'vendor')) {
            $vendor  = MobbexVendor::getVendors(true, 'id', MobbexCustomFields::getCustomField($productId, 'producto', 'vendor'));
            if ($vendor['fee'])
                return $vendor['fee'];
        }

        //return general fee
        return Configuration::get(self::K_FEE);
    }

    public static function getMarketplaceitems($products, $cart_total, $mobbex_total)
    {
        $items = [];
        foreach ($products as $product) {

            $vendor_id = MobbexCustomFields::getCustomField($product['id_product'], 'product', 'vendor');
            $vendor    = MobbexVendor::getVendors(true, 'id', $vendor_id);
            $fee       = MarketplaceHelper::getProductFee($product['id_product']);
            $dif       = ($cart_total / $mobbex_total * 100) - 100;
            $dif       = $dif <= 9 ? '0.0' . $dif : '0.' . $dif;
            
            $items[$product['id_product']]['name']          = $product['name'];
            $items[$product['id_product']]['quantity']      = $product['quantity'];
            $items[$product['id_product']]['total']         = round($product['price_wt'] + ($product['price_wt'] * $dif), 2);
            $items[$product['id_product']]['fee_amount']    = $fee;
            $fee                                            = $fee <= 9 ? '0.0' . $fee : '0.' . $fee;
            $items[$product['id_product']]['fee']           = round(($product['price_wt'] + ($product['price_wt'] * $dif)) * $fee, 2);
            $items[$product['id_product']]['vendor_name']   = $vendor[0]['name'] ?: '';
            $items[$product['id_product']]['vendor_tax_id'] = $vendor[0]['tax_id'] ?: '';
            $items[$product['id_product']]['vendor_hold']   = $vendor[0]['hold'] == 1 ? 'YES' : 'NO';

        }
        return $items;
    }
}
