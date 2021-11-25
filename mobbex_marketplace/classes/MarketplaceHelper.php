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
    public static function getProductVendors($products)
    {
        $vendors = [];

        foreach ($products as $product) {
            $vendor = MobbexCustomFields::getCustomField($product['id_product'], 'product', 'vendor');
            $vendor = MobbexVendor::getVendors(true, 'id', $vendor);
            $vendor_id = $vendor[0]['tax_id'] ?: '';

            if (empty($tax_id))
                return [];

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
        if(MobbexCustomFields::getCustomField($productId, 'product', 'vendor')) {
            $vendor  = MobbexVendor::getVendors(true, 'id', MobbexCustomFields::getCustomField($productId, 'producto', 'vendor'));
            if ($vendor['fee'])
                return $vendor['fee'];
        }

        //return general fee
        return Configuration::get(self::K_FEE);
    }
}