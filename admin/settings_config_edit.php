<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!Main\Loader::includeModule("w4a.tendercalc")) {
    return;
}

use W4a\Tendercalc;
echo Tendercalc\Config::getTest();