<?php

namespace Mobbex\PS\Marketplace\Models;

class Transaction extends \Mobbex\PS\Checkout\Models\AbstractModel
{
    public $id;
    public $payment_id;
    public $operation_type;
    public $data;

    public static $definition = [
        'table' => 'mobbex_marketplace_transaction',
        'primary' => 'id',
        'fields' => [
            'payment_id'     => ['type' => self::TYPE_STRING, 'required' => false],
            'operation_type' => ['type' => self::TYPE_STRING, 'required' => false],
            'data'           => ['type' => self::TYPE_STRING, 'required' => false],
        ],
    ];

    /**
     * Get marketplace transaction from database. 
     * @param string
     * @return array
     */
    public static function getTrx($id)
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
    public static function saveTransaction($payment_id, $operation_type, $data)
    {
        $trx = new \Mobbex\PS\Marketplace\Models\Transaction();

        $trx->payment_id     = $payment_id;
        $trx->operation_type = $operation_type;
        $trx->data           = $data;      

        $trx->save();
    }
}