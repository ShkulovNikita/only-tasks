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
        /*
         * Добавить хайлоад-блок для марок автомобилей.
         */
        $carBrandsHlBlockId = $this->carBrandUp($helper);
        /*
         * Добавить раздел инфоблоков.
         */
        $this->iblockSectionUp($helper);
        /*
         * Добавить инфоблок водителей.
         */
        $driversIblockId = $this->driversUp($helper);
        /*
         * Добавить категорию комфорта .
         */
        $combofrtabilityHlBlockId = $this->comfortabilityUp($helper);
    }

    /**
     * Откатить миграцию.
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        /**
         * Удалить категорию комфорта.
         */
        $this->comfortabilityDown($helper);
        /*
         * Удалить инфоблок водителей.
         */
        $this->driversDown($helper);
        /*
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
                    'FIELD_NAME' => 'UF_CAR_BRAND_NAME', 
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => 'UF_CAR_BRAND_LOGO', 
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
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function iblockSectionUp($helper)
    {
        $helper->Iblock()->saveIblockType([
            'ID' => $this->iblockSectionID,
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
     * Добавить инфоблок водителей служебных автомобилей.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @return int Идентификатор созданного инфоблока.
     */
    private function driversUp($helper)
    {
        $driversIblockId = $helper->Iblock()->saveIblock([
            'NAME' => 'Водители',
            'CODE' => 'job_cars_drivers',
            'LID' => ['s1'],
            'IBLOCK_TYPE_ID' => $this->iblockSectionID,
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/news/#ELEMENT_ID#',
        ]);
        
        $helper->Iblock()->saveIblockFields($driversIblockId, [
            'CODE' => [
                'DEFAULT_VALUE' => [
                    'TRANSLITERATION' => 'Y',
                    'UNIQUE' => 'Y',
                ],
            ],
        ]);

        $arProps = [
            [
                'NAME' => 'Фамилия',
                'CODE' => 'SURNAME',
                'PROPERTY_TYPE' => 'S',
                'IS_REQUIRED' => 'Y'
            ],
            [
                'NAME' => 'Имя',
                'CODE' => 'NAME',
                'PROPERTY_TYPE' => 'S',
                'IS_REQUIRED' => 'Y'
            ],
            [
                'NAME' => 'Отчество',
                'CODE' => 'PATRONYMIC',
                'PROPERTY_TYPE' => 'S'
            ],
            [
                'NAME' => 'Номер телефона',
                'CODE' => 'PHONE',
                'PROPERTY_TYPE' => 'S'
            ]
        ];
        if ($driversIblockId) {
            foreach ($arProps as $arProp) {
                $helper->Iblock()->addPropertyIfNotExists($driversIblockId, $arProp);
            }

            $helper->AdminIblock()->buildElementForm($driversIblockId, [
                'Личные данные' => [
                    'PROPERTY_SURNAME',
                    'PROPERTY_NAME',
                    'PROPERTY_PATRONYMIC',
                    'PROPERTY_PHONE'
                ]
            ]);
        }

        return $driversIblockId;
    }

    /**
     * Добавить справочник категории комфорта.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @return int Идентификатор созданного хайлоад-блока.
     */
    private function comfortabilityUp($helper)
    {
        $combofrtabilityHlBlockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'ComfortabilityCategory',
            'TABLE_NAME' => 'hl_comfortability_category',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $combofrtabilityHlBlockId,
            [
                [
                    'FIELD_NAME' => 'UF_COMFORTABILITY_CATEGORY_NAME',
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability class'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability class'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability class'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ]
            ]
        );

        return $combofrtabilityHlBlockId;
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
                'UF_CAR_BRAND_NAME',
                'UF_CAR_BRAND_LOGO'
            ]
        );
        $helper->Hlblock()->deleteHlblock($carBrandsHlBlockId);
    }

    /**
     * Удалить инфоблок водителей.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function driversDown($helper)
    {
        $result = $helper->Iblock()->deleteIblockIfExists('job_cars_drivers');
    }

    /**
     * Удаление хайлоад-блока с категориями комфорта.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function comfortabilityDown($helper)
    {
        $combofrtabilityHlBlockId = $helper->Hlblock()->getHlblockIdIfExists('ComfortabilityCategory');
       
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $combofrtabilityHlBlockId,
            [
                'UF_COMFORTABILITY_CATEGORY_NAME'
            ]
        );
        $helper->Hlblock()->deleteHlblock($combofrtabilityHlBlockId);
    }
}
