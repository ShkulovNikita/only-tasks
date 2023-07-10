<?php

use \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class trainee_cprop extends CModule
{
    public $MODULE_ID = 'trainee.cprop';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = 'N';

    function __construct()
    {
        /**
         * Занести в массив $arModuleVersion данные о версии.
         */
        $arModuleVersion = array();
        include __DIR__ . '/version.php';

        $this->MODULE_ID = 'trainee.cprop';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('IEX_COMPLEX_PROP_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('IEX_COMPLEX_PROP_MODULE_DESC');
    
        $this->FILE_PREFIX = 'trainee';
        $this->MODULE_FOLDER = str_replace('.', '_', $this->MODULE_ID);
        $this->FOLDER = 'bitrix';

        $this->INSTALL_PATH_FROM = '/' . $this->FOLDER . '/modules/' . $this->MODULE_ID;
    }

    function DoInstall()
    {
        global $APPLICATION;
        if ($this->isVersionD7()) {

            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();      

            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        } else {
            $APPLICATION->ThrowException(GetMessage('IEX_COMPLEX_PROP_INSTALL_ERROR_VERSION'));
        }
    }

    function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();
    }

    function isVersionD7() 
    {
        return true;
    }

    function InstallDB()
    {
        return true;
    }

    function UnInstallDB()
    {
        return true;
    }

    function InstallFiles()
    {
        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }

    /**
     * Зарегистрировать обработчик события из модуля для события
     * построения списка пользовательских свойств инфоблока.
     */
    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        /*
         * Добавить комплексное свойство. 
         */
        $eventManager->registerEventHandler(
            'iblock', 
            'OnIBlockPropertyBuildList', 
            $this->MODULE_ID,
            'CIBlockPropertyComplexProp',
            'GetUserTypeDescription'
        );
        /*
         * Добавить пользовательское поле. 
         */
        $eventManager->registerEventHandler(
            'main',
            'OnUserTypeBuildList',
            $this->MODULE_ID,
            'CComplexUserField',
            'GetUserTypeDescription'
        );

        return true;
    }

    /**
     * Отвязать обработчики событий модуля.
     */
    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unregisterEventHandler(
            'iblock', 
            'OnIBlockPropertyBuildList', 
            $this->MODULE_ID,
            'CIBlockPropertyComplexProp',
            'GetUserTypeDescription'
        );
        $eventManager->unregisterEventHandler(
            'main',
            'OnUserTypeBuildList',
            $this->MODULE_ID,
            'CComplexUserField',
            'GetUserTypeDescription'
        );

        return true;
    }
}