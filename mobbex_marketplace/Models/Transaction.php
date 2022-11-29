<?php

namespace Mobbex\PS\Marketplace\Models;

class Transaction extends \ObjectModel
{
    public $id;
    public $payment_id;
    public $data;

    public static $definition = [
        'table' => 'mobbex_marketplace_transaction',
        'primary' => 'id',
        'fields' => [
            'payment_id'  => ['type' => self::TYPE_STRING, 'required' => false],
            'data'        => ['type' => self::TYPE_STRING, 'required' => false],
        ],
    ];

    /**
     * Get Vendor transaction from database, you can filter to obtain an specific transaction. 
     * @param bool
     * @param string
     * @param string
     * @return array
     */
    public static function getData($id)
    {
        $sql = new \DbQuery();
        $sql->select('*');
        $sql->where("payment_id = '$id'");
        $sql->from('mobbex_marketplace_transaction', 'f');

        $result = \Db::getInstance()->executeS($sql);
        return $result ? $result[0] : false;
    }

    /**
     * Saves the transaction with the data
     */
    public static function saveTransaction($payment_id, $data)
    {
        $trx = new \Mobbex\PS\Marketplace\Models\Transaction();

        $trx->payment_id = $payment_id;
        $trx->data       = $data;      

        $trx->save();
    }
}