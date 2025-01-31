<?php

defined('_PS_VERSION_') || exit;

function upgrade_module_1_2_0($module) {
    $db = DB::getInstance();

    // Remove tax id and change created to updated
    return $db->execute(
        "ALTER TABLE `" . _DB_PREFIX_ . "mobbex_vendor` DROP `tax_id`, CHANGE `created` `updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;"
    );
}
