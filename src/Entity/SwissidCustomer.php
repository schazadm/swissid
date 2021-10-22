<?php
/** ====================================================================
 *
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * @author             Online Services Rieder GmbH
 * @copyright          Online Services Rieder GmbH
 * @license            Check at: https://www.os-rieder.ch/
 * @date:              22.10.2021
 * @version:           1.0.0
 * @name:              SwissID
 * @description        Provides the possibility for a customer to log in with his SwissID.
 * @website            https://www.os-rieder.ch/
 *
 * ================================================================== **/

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
     * @return bool
     */
    public static function isCustomerLinkedById($customer_id)
    {
        if (!self::checkCustomerId($customer_id)) {
            return false;
        }
        try {
            $sql = new DbQuery();
            $sql->select('sc.*');
            $sql->from(SwissidCustomer::$definition['table'], 'sc');
            $sql->where('sc.id_customer = ' . (int)$customer_id);
            $result = Db::getInstance()->getValue($sql);
            return (bool)$result;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Checks whether a customer with the given customer id has the age_over set to 1
     *
     * @param int $customer_id
     * @return bool
     */
    public static function isCustomerAgeOver($customer_id)
    {
        if (!self::checkCustomerId($customer_id)) {
            return false;
        }
        try {
            $sql = new DbQuery();
            $sql->select('sc.*');
            $sql->from(SwissidCustomer::$definition['table'], 'sc');
            $sql->where('sc.id_customer = ' . (int)$customer_id);
            $sql->where('sc.age_over = 1');
            $result = Db::getInstance()->getValue($sql);
            return (bool)$result;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Updates the age over of the given customer
     *
     * @param int $customer_id
     * @param $ageOver
     * @return bool
     */
    public static function updateCustomerAgeOver($customer_id, $ageOver)
    {
        if (!self::checkCustomerId($customer_id)) {
            return false;
        }
        try {
            $sql = '
            UPDATE `' . _DB_PREFIX_ . SwissidCustomer::$definition['table'] .
                '` SET `age_over`=' . (int)$ageOver .
                ' WHERE `id_customer`=' . (int)$customer_id;
            return Db::getInstance()->execute($sql);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Adds an entry with the given customer id
     * optionally age over
     *
     * @param int $customer_id
     * @param int $ageOver
     * @return bool
     */
    public static function addSwissidCustomer($customer_id, $ageOver = 0)
    {
        if (!self::checkCustomerId($customer_id)) {
            return false;
        }
        try {
            $sql = '
            INSERT INTO `' . _DB_PREFIX_ . SwissidCustomer::$definition['table'] . '` (`id_customer`, `age_over`)
            VALUES (' . (int)$customer_id . ', ' . (int)$ageOver . ')';
            return Db::getInstance()->execute($sql);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Deletes an entry based on the given customer id
     *
     * @param int $customer_id
     * @return bool
     */
    public static function removeSwissidCustomerByCustomerId($customer_id)
    {
        if (!self::checkCustomerId($customer_id)) {
            return false;
        }
        try {
            $sql = '
            DELETE FROM `' . _DB_PREFIX_ . SwissidCustomer::$definition['table'] . '`
            WHERE `id_customer` = "' . (int)$customer_id . '"';
            return Db::getInstance()->execute($sql);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Checks the given customer id if it's not empty or null
     *
     * @param $customer_id
     * @return bool
     */
    private static function checkCustomerId($customer_id)
    {
        if (empty($customer_id) || $customer_id == null) {
            return false;
        }
        return true;
    }
}
