<?php
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
