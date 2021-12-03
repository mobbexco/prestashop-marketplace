<?php

class MobbexVendor extends ObjectModel
{
    public $id;
    public $tax_id;
    public $name;
    public $fee;
    public $hold;
    public $created;


    public static $definition = [
        'table' => 'mobbex_vendor',
        'primary' => 'id',
        'fields' => [
            'tax_id'      => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255, 'required' => true],
            'name'        => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size'     => 255, 'required' => true],
            'fee'         => ['type' => self::TYPE_INT, 'validate'    => 'isAnything', 'size' => 255, 'required' => false],
            'hold'        => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'size' => 255, 'required' => false],
            'created'     => ['type' => self::TYPE_DATE, 'validate'   => 'isDateFormat'],
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
        $sql = new DbQuery();
        $sql->select('*');
        if($filter) {
            $query = $paramName . ' = ' . $param;
            $sql->where($query);
        }
        $sql->from('mobbex_vendor', 'f');

        $result = Db::getInstance()->executeS($sql);
        return !empty($result) ? $result : false;
    }

}