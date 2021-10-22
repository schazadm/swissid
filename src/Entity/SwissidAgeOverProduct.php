<?php
/**
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
 */

/**
 * Class SwissidAgeOverProduct
 *
 * Definition of the swissid_product database table.
 * Handles related database queries and executes them.
 */
class SwissidAgeOverProduct extends ObjectModel
{
    /**
     * @var int Product identifier
     */
    public $id_product;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'swissid_age_over_product',
        'primary' => 'id_swissid_age_over_product',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10],
        ]
    ];

    /**
     * Checks whether a product with the given product id exists in in the table
     *
     * @param int $product_id
     * @return bool
     */
    public static function isProductInTable($product_id)
    {
        if (!self::checkProductId($product_id)) {
            return false;
        }
        try {
            $sql = new DbQuery();
            $sql->select('saop.*');
            $sql->from(SwissidAgeOverProduct::$definition['table'], 'saop');
            $sql->where('saop.id_product = ' . (int)$product_id);
            $result = Db::getInstance()->getValue($sql);
            return (bool)$result;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Deletes an entry based on the given product id
     *
     * @param int $product_id
     * @return bool
     */
    public static function removeAgeOverProductByProductId($product_id)
    {
        if (!self::checkProductId($product_id)) {
            return false;
        }
        try {
            $sql = '
            DELETE FROM `' . _DB_PREFIX_ . SwissidAgeOverProduct::$definition['table'] . '`
            WHERE `id_product` = "' . (int)$product_id . '"';
            return Db::getInstance()->execute($sql);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Checks the given product id if it's not empty or null
     *
     * @param $product_id
     * @return bool
     */
    private static function checkProductId($product_id)
    {
        if (empty($product_id) || $product_id == null) {
            return false;
        }
        return true;
    }
}
