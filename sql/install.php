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

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'swissid_customer` (
    `id_swissid_customer` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT(10) UNSIGNED UNIQUE NOT NULL,
    `age_over` TINYINT(1) UNSIGNED DEFAULT 0,
    PRIMARY KEY  (`id_swissid_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'swissid_age_over_product` (
    `id_swissid_age_over_product` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(10) UNSIGNED UNIQUE NOT NULL,
    PRIMARY KEY  (`id_swissid_age_over_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
