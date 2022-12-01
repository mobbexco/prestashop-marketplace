<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This function updates your module from previous versions to the version 1.1,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_1_1_0($module)
{
    $db = DB::getInstance();

    $db->execute(
        "ALTER TABLE `" . _DB_PREFIX_ . "mobbex_vendor` ADD `uid` TEXT NOT NULL;"
    );

    $db->execute(
        "ALTER TABLE `" . _DB_PREFIX_ . "mobbex_marketplace_transaction` ADD `operation_type` TEXT NOT NULL;"
    );

    return true;
}
