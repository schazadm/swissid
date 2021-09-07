<?php


class SwissidUser extends ObjectModel
{
    /**
     * @var int Customer identifier
     */
    public $id_customer;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'swissid_user',
        'primary' => 'id_swissid_user',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10],
        ]
    ];
}