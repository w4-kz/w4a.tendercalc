<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
if (!CModule::IncludeModule("w4a.tendercalc")) {
    return;
}

$aMenu = array(
    "parent_menu" => "global_menu_settings",
    "section" => "W4A_TENDERCALC",
    "sort" => 9000,
    "text" => GetMessage("W4A_TENDERCALC_MENU_MAIN"),
    "title" => "",
    "icon" => "w4a_tendercalc_menu_menu_icon",
    "page_icon" => "w4a_tendercalc_menu_page_icon",
    "items_id" => "menu_w4a_tendercalc_list",
    "items" => array()
);
$aMenu["items"][1] = array(
    "text" => GetMessage("W4A_TENDERCALC_MENU_SETTING"),
    "items_id" => "menu_ir_settings",
    "items" => array()
);
$aMenu["items"][1]["items"][] = array(
    "text" => GetMessage("W4A_TENDERCALC_MENU_SETTING_CONFIG"),
    "url" => "perfmon_table.php?lang=ru&table_name=w4a_tendercalc_config&lang=" . LANGUAGE_ID,
    //"url" => "w4a_tendercalc_settings_config.php?lang=" . LANGUAGE_ID,
    //"more_url" => Array("settings_config_edit.php"),
);
return $aMenu;