<?php

defined('_PS_VERSION_') || exit;

function upgrade_module_1_2_1($module) {
    return $module->runMigrations();
}
