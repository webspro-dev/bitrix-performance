<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class stadigit_performance extends CModule
{
    public $MODULE_ID = 'stadigit.performance';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Stadigit';
    public $PARTNER_URI = 'https://github.com/stadigit';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('STADIGIT_PERFORMANCE_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('STADIGIT_PERFORMANCE_MODULE_DESC');
    }

    public function DoInstall()
    {
        global $APPLICATION;

        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('STADIGIT_PERFORMANCE_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    public function InstallFiles()
    {
        $source = dirname(__DIR__) . '/admin/performance_dashboard.php';
        $destination = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/performance_dashboard.php';

        if (is_file($source)) {
            CopyDirFiles($source, $destination, true, true);
        }

        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('STADIGIT_PERFORMANCE_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );
    }

    public function UnInstallFiles()
    {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/performance_dashboard.php';

        if (is_file($file)) {
            @unlink($file);
        }

        return true;
    }
}
