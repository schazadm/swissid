<?php


class SwissidCustomer extends ObjectModel
{
    /**
     * @var int Customer identifier
     */
    public $id_customer;

    /**
     * @var int Customer age verification over +18
     */
    public $age_over;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'swissid_customer',
        'primary' => 'id_swissid_customer',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10],
            'age_over' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ]
    ];
}