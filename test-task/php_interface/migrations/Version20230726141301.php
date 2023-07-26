<?php

namespace Sprint\Migration;


class Version20230726141301 extends Version
{
    protected $description = "Создать инфоблоки и highload-блоки для компонента выбора служебного автомобиля.";

    protected $moduleVersion = "4.2.4";

    private $iblockSectionID = 'job_cars';

    /**
     * Установить миграцию.
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        /**
         * Добавление хайлоад-блока для марок автомобилей.
         */
        $carBrandsHlBlockId = $this->carBrandUp($helper);
        /**
         * Добавить раздел инфоблоков.
         */
        $this->iblockSectionUp($this->iblockSectionID, $helper);
    }

    /**
     * Откатить миграцию.
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        /**
         * Удалить хайлоад-блок марок автомобилей.
         */
        $this->carBrandDown($helper);
    }

    /**
     * Добавить хайлоад-блок для марки автомобиля.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @return int Идентификатор добавленного хайлоад-блока.
     */
    private function carBrandUp($helper)
    {
        $carBrandsHlBlockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CarBrand',
            'TABLE_NAME' => 'hl_car_brand',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $carBrandsHlBlockId,
            [
                [
                    'FIELD_NAME' => 'UF_NAME', 
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => 'UF_LOGO', 
                    'USER_TYPE_ID' => 'file',
                    'MANDATORY' => 'N',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Логотип', 'en' => 'Brand logo'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Логотип', 'en' => 'Brand logo'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Логотип', 'en' => 'Brand logo'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ]
            ]
        );

        return $carBrandsHlBlockId;
    }

    /**
     * Добавить раздел для добавляемых инфоблоков.
     * @param int $iblockSectionID Идентификатор раздела инфоблоков.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function iblockSectionUp($iblockSectionID, $helper)
    {
        $helper->Iblock()->saveIblockType([
            'ID' => $iblockSectionID,
            'LANG' => [
                'en' => [
                    'NAME' => 'Job cars',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
                'ru' => [
                    'NAME' => 'Служебные поездки',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы',
                ],
            ],
        ]);
    }

    /**
     * Удалить хайлоад-блок марки автомобиля.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function carBrandDown($helper)
    {
        $carBrandsHlBlockId = $helper->Hlblock()->getHlblockIdIfExists('CarBrand');

        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $carBrandsHlBlockId,
            [
                'UF_NAME',
                'UF_LOGO'
            ]
        );
        $helper->Hlblock()->deleteHlblock($carBrandsHlBlockId);
    }
}
