<?php
defined('B_PROLOG_INCLUDED') || die;
IncludeModuleLangFile(__FILE__);

use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\Localization\Loc;

Class w4a_tendercalc extends CModule
{
    const MODULE_ID = 'w4a.tendercalc';
    var $MODULE_ID = self::MODULE_ID;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $strError;

    function __construct()
    {
        include(dirname(__FILE__) . '/version.php');
        if(!isset($arModuleVersion['VERSION']))
        {
            $arModuleVersion = array(
               'VERSION' => '1.0.0',
                'VERSION_DATE' => '2020-09-30 07:20:00'
            );
        }
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('W4A_TENDERCALC.MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('W4A_TENDERCALC.MODULE_DESC');
        $this->PARTNER_NAME = Loc::getMessage('W4A_TENDERCALC.PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('W4A_TENDERCALC.PARTNER_URI');
    }

    function DoInstall()
    {
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);
        return true;
    }

    function DoUninstall()
    {
        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        UnRegisterModule($this->MODULE_ID);
        return true;
    }

    function InstallDB()
    {
        global $DB;
        $this->strError = false;
        $this->strError = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/local/modules/{$this->MODULE_ID}/install/db/install.sql");
        if (!$this->strError) {

            return true;
        } else
            return $this->strError;
    }

    function UnInstallDB()
    {
        global $DB;
        $this->strError = false;
        $this->strError = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/local/modules/{$this->MODULE_ID}/install/db/uninstall.sql");
        if (!$this->strError) {
            return true;
        } else
            return $this->strError;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function InstallFiles()
    {
        $documentRoot = Application::getDocumentRoot();

        CopyDirFiles(
            __DIR__ . '/files/components',
            $documentRoot . '/local/components',
            true,
            true
        );
        CopyDirFiles(
            __DIR__ . '/files/w4a_apps',
            $documentRoot . '/w4a_apps',
            true,
            true
        );
        return true;
    }

    function UnInstallFiles()
    {
//        DeleteDirFilesEx('/local/components/w4a/tendercalc.product.list');
//        DeleteDirFilesEx('/w4a_apps/widget/tendercalc');
        return true;
    }
}
