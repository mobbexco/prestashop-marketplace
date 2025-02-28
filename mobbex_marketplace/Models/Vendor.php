<?php

namespace Mobbex\PS\Marketplace\Models;

class Vendor extends \Mobbex\PS\Checkout\Models\AbstractModel
{
    public $id;
    public $uid;
    public $name;
    public $fee;
    public $hold;
    public $updated;

    public static $definition = [
        'table' => 'mobbex_vendor',
        'primary' => 'id',
        'fields' => [
            'uid'         => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255, 'required' => true],
            'name'        => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255, 'required' => true],
            'fee'         => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255, 'required' => false],
            'hold'        => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255, 'required' => false],
            'updated'     => ['type' => self::TYPE_DATE,   'validate' => 'isDateFormat', 'default' => 'NOW()', 'required' => false],
        ],
    ];

    /**
     * Get Vendors from database, you can filter to obtain an specific vendor. 
     * @param bool
     * @param string
     * @param string
     * @return array
     */
    public static function getVendors($filter = null, $paramName = null, $param = null)
    {
        $sql = new \DbQuery();
        $sql->select('*');

        if($filter) {
            $query = $paramName . ' = ' . $param;
            $sql->where($query);
        }

        $sql->from('mobbex_vendor', 'f');
        $result = $filter ? \Db::getInstance()->executeS($sql)[0] : \Db::getInstance()->executeS($sql);
        
        return !empty($result) ? $result : false;
    }

}