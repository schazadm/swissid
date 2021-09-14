<?php

/**
 * Class SwissidCustomer
 *
 * Definition of the swissid_customer database table.
 * Handles related database queries and executes them.
 */
class SwissidCustomer extends ObjectModel
{
    /**
     * @var int Customer identifier
     */
    public $id_customer;

    /**
     * @var int Customer age verification over 18+
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

    /**
     * Checks whether a customer with the given customer id exists in in the main table
     *
     * @param int $customer_id
     *
     * @return bool
     */
    public static function isCustomerLinkedById($customer_id)
    {
        try {
            $sql = new DbQuery();
            $sql->select('sc.*');
            $sql->from(SwissidCustomer::$definition['table'], 'sc');
            $sql->where('sc.id_customer = ' . (int)$customer_id);
            $result = Db::getInstance()->getValue($sql);
            return (bool)$result;
        } catch (PrestaShopException $exception) {
            return false;
        }
    }
}