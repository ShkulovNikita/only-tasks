<?php

namespace Sprint\Migration;


class CREATE_DATA_STRUCTURE20230727101917 extends Version
{
    protected $description = "Создать инфоблоки и highload-блоки для компонента выбора служебного автомобиля.";

    protected $moduleVersion = "4.2.4";

    /**
     * Код добавляемого раздела для инфоблоков.
     * @var string
     */
    private $iblockSectionID = 'job_cars';
    
    /**
     * Массив с символьными кодами добавляемых инфоблоков и их полей.
     * @var array 
     */
    private $iblockCodes = [
        'DRIVERS' => [
            'CODE' => 'job_cars_drivers'
        ],
        'CARS' => [
            'CODE' => 'job_cars'
        ]
    ];

    /**
     * Массив с кодами добавляемых хайлоад-блоков и их полей.
     * @var array
     */
    private $hlblockCodes = [
        'BRANDS' => [
            'NAME' => 'CarBrands',
            'FIELDS' => [
                'NAME' => 'UF_NAME',
                'XML' => 'UF_XML_ID',
                'LOGO' => 'UF_CAR_BRAND_LOGO'
            ]
        ],
        'COMFORTABILITY' => [
            'NAME' => 'ComfortabilityCategories',
            'FIELDS' => [
                'NAME' => 'UF_NAME',
                'XML' => 'UF_XML_ID',
            ]
        ],
        'POSITIONS' => [
            'NAME' => 'Positions',
            'FIELDS' => [
                'NAME' => 'UF_NAME',
                'XML' => 'UF_XML_ID',
            ]
        ],
        'MODELS' => [
            'NAME' => 'CarModels',
            'FIELDS' => [
                'NAME' => 'UF_NAME',
                'XML' => 'UF_XML_ID',
                'PHOTO' => 'UF_CAR_MODEL_PHOTO',
                'BRAND' => 'UF_CAR_BRAND_OF_MODEL',
                'COMFORTABILITY' => 'UF_CAR_COMFORTABILITY_OF_MODEL'
            ]
        ],
        'COMFORTABILITY_AVAILABLE' => [
            'NAME' => 'ComfortabilityAvailability',
            'FIELDS' => [
                'POSITION' => 'UF_COMF_AVAIL_POSITION',
                'CATEGORY' => 'UF_COMF_AVAIL_CATEGORY'
            ]
        ],
        'CAR_BOOKING' => [
            'NAME' => 'JobCarBookings',
            'FIELDS' => [
                'START' => 'UF_CAR_BOOKING_START',
                'END' => 'UF_CAR_BOOKING_END',
                'USER' => 'UF_CAR_BOOKING_USER_ID',
                'CAR' => 'UF_CAR_BOOKING_CHOSEN_CAR'
            ]
        ]
    ];

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
         * Добавить категории комфорта.
         */
        $comfortabilityHlBlockId = $this->comfortabilityUp($helper);
        /*
         * Добавить должности.
         */
        $positionHlBlockId = $this->positionUp($helper);
        /*
         * Добавить модели автомобилей. 
         */
        $carModelsHlBlockId = $this->carModelUp(
            $helper, 
            $carBrandsHlBlockId, 
            $comfortabilityHlBlockId
        );
        /*
         * Добавить доступность категорий годности по должностям.
         */
        $comfAvalHlBlockId = $this->comfortabilityAvailabilityUp(
            $helper,
            $positionHlBlockId,
            $comfortabilityHlBlockId
        );
        /*
         * Добавить автомобили. 
         */
        $carIblockId = $this->carUp($helper, $driversIblockId);
        /*
         * Добавить бронирование автомобилей. 
         */
        $carBookingHlblockId = $this->carBookingUp($helper, $carIblockId);
    }

    /**
     * Откатить миграцию.
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        /*
         * Удалить хайлоад-блок бронирования автомобилей. 
         */
        $this->carBookingDown($helper);
        /*
         * Удалить инфоблок автомобилей. 
         */
        $this->carDown($helper);
        /*
         * Удалить сопоставление должностей и категорий комфорта. 
         */
        $this->comfortabilityAvailabilityDown($helper);
        /*
         * Удалить модели автомобилей. 
         */
        $this->carModelDown($helper);
        /*
         * Удалить должности 
         */
        $this->positionDown($helper);
        /*
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
            'NAME' => $this->hlblockCodes['BRANDS']['NAME'],
            'TABLE_NAME' => 'hl_car_brand',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $carBrandsHlBlockId,
            [
                [
                    'FIELD_NAME' => $this->hlblockCodes['BRANDS']['FIELDS']['NAME'], 
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Название', 'en' => 'Brand name'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['BRANDS']['FIELDS']['XML'],
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['BRANDS']['FIELDS']['LOGO'], 
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
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
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
            'CODE' => $this->iblockCodes['DRIVERS']['CODE'],
            'LID' => ['s1'],
            'IBLOCK_TYPE_ID' => $this->iblockSectionID,
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/car_drivers/#ELEMENT_ID#',
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
                $helper->Iblock()->addPropertyIfNotExists(
                    $driversIblockId, 
                    $arProp
                );
            }

            $helper->AdminIblock()->buildElementForm($driversIblockId, [
                'Личные данные' => [
                    'NAME' => 'Фамилия',
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
        $comfortabilityHlBlockId = $helper->Hlblock()->saveHlblock([
            'NAME' => $this->hlblockCodes['COMFORTABILITY']['NAME'],
            'TABLE_NAME' => 'hl_comfortability_category',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $comfortabilityHlBlockId,
            [
                [
                    'FIELD_NAME' => $this->hlblockCodes['COMFORTABILITY']['FIELDS']['NAME'],
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability class'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability class'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability class'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['COMFORTABILITY']['FIELDS']['XML'],
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
            ]
        );

        return $comfortabilityHlBlockId;
    }

    /**
     * Создать справочник с должностями.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @return int Идентификатор созданного хайлоад-блока.
     */
    private function positionUp($helper)
    {
        $positionHlBlockId = $helper->Hlblock()->saveHlblock([ 
            'NAME' => $this->hlblockCodes['POSITIONS']['NAME'],
            'TABLE_NAME' => 'hl_jobcars_positions',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $positionHlBlockId,
            [
                [
                    'FIELD_NAME' => $this->hlblockCodes['POSITIONS']['FIELDS']['NAME'],
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Должность', 'en' => 'Comfortability class'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Должность', 'en' => 'Comfortability class'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Должность', 'en' => 'Comfortability class'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['POSITIONS']['FIELDS']['XML'],
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
            ]
        );

        return $positionHlBlockId;
    }

    /**
     * Добавить справочник моделей автомобилей.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @param int $brandId Идентификатор хайлоад-блока с марками автомобилей.
     * @param int $comfortabilityId Идентификатор хайлоад-блока с категориями комфорта.
     * @return int Идентификатор созданного хайлоад-блока.
     */
    private function carModelUp($helper, $brandId, $comfortabilityId)
    {
        $carModelsHlBlockId = $helper->Hlblock()->saveHlblock([
            'NAME' => $this->hlblockCodes['MODELS']['NAME'],
            'TABLE_NAME' => 'hl_car_model',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $carModelsHlBlockId,
            [
                [
                    'FIELD_NAME' => $this->hlblockCodes['MODELS']['FIELDS']['NAME'],
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Название', 'en' => 'Model name'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Название', 'en' => 'Model name'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Название', 'en' => 'Model name'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['MODELS']['FIELDS']['XML'],
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'XML_ID', 'en' => 'XML_ID'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['MODELS']['FIELDS']['PHOTO'],
                    'USER_TYPE_ID' => 'file',
                    'MANDATORY' => 'N',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Изображение', 'en' => 'Model photo'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Изображение', 'en' => 'Model photo'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Изображение', 'en' => 'Model photo'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['MODELS']['FIELDS']['BRAND'],
                    'USER_TYPE_ID' => 'hlblock',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Марка', 'en' => 'Car brand'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Марка', 'en' => 'Car brand'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Марка', 'en' => 'Car brand'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'SETTINGS' => Array(
                        'LIST_HEIGHT' => 3,
                        'HLBLOCK_ID' => $brandId,
                        'HLFIELD_ID' => $helper->Hlblock()->getFieldIdByUid(
                            $this->hlblockCodes['BRANDS']['NAME'],
                            $this->hlblockCodes['BRANDS']['FIELDS']['NAME']
                        )
                    )
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['MODELS']['FIELDS']['COMFORTABILITY'],
                    'USER_TYPE_ID' => 'hlblock',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Категория комфорта', 'en' => 'Comfortability'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'SETTINGS' => Array(
                        'LIST_HEIGHT' => 3,
                        'HLBLOCK_ID' => $comfortabilityId,
                        'HLFIELD_ID' => $helper->Hlblock()->getFieldIdByUid(
                            $this->hlblockCodes['COMFORTABILITY']['NAME'],
                            $this->hlblockCodes['COMFORTABILITY']['FIELDS']['NAME']
                        )
                    )
                ]
            ]
        );

        return $carModelsHlBlockId;
    }

    /**
     * Добавить справочник с соответствием между должностями и категориями комфорта.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @param int $positionId Идентификатор хайлоад-блока с должностями.
     * @param int $comfId Идентификатор хайлоад-блока с категориями комфорта.
     * @return int Идентификатор созданного хайлоад-блока.
     */
    private function comfortabilityAvailabilityUp($helper, $positionId, $comfId)
    {
        $comfAvalHlBlockId = $helper->Hlblock()->saveHlBlock([
            'NAME' => $this->hlblockCodes['COMFORTABILITY_AVAILABLE']['NAME'],
            'TABLE_NAME' => 'hl_comfortability_availability',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $comfAvalHlBlockId,
            [
                [
                    'FIELD_NAME' => $this->hlblockCodes['COMFORTABILITY_AVAILABLE']['FIELDS']['POSITION'],
                    'USER_TYPE_ID' => 'hlblock',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Должность', 'en' => 'Position'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Должность', 'en' => 'Position'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Должность', 'en' => 'Position'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'SETTINGS' => Array(
                        'LIST_HEIGHT' => 3,
                        'HLBLOCK_ID' => $positionId,
                        'HLFIELD_ID' => $helper->Hlblock()->getFieldIdByUid(
                            $this->hlblockCodes['POSITIONS']['NAME'],
                            $this->hlblockCodes['POSITIONS']['FIELDS']['NAME']
                        )
                    )
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['COMFORTABILITY_AVAILABLE']['FIELDS']['CATEGORY'],
                    'USER_TYPE_ID' => 'hlblock',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Доступная категория комфорта', 'en' => 'Available comfortability class'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Доступная категория комфорта', 'en' => 'Available comfortability class'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Доступная категория комфорта', 'en' => 'Available comfortability class'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'SETTINGS' => Array(
                        'LIST_HEIGHT' => 3,
                        'HLBLOCK_ID' => $comfId,
                        'HLFIELD_ID' => $helper->Hlblock()->getFieldIdByUid(
                            $this->hlblockCodes['COMFORTABILITY']['NAME'],
                            $this->hlblockCodes['COMFORTABILITY']['FIELDS']['NAME']
                        )
                    )
                ]
            ]
        );

        return $comfAvalHlBlockId;
    }

    /**
     * Добавить инфоблок служебных автомобилей.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @param int $driverId Идентификатор инфоблока водителей.
     * @return int Идентификатор созданного инфоблока.
     */
    private function carUp($helper, $driverId)
    {
        $carsIblockId = $helper->Iblock()->saveIblock([
            'NAME' => 'Автомобили',
            'CODE' => $this->iblockCodes['CARS']['CODE'],
            'LID' => ['s1'],
            'IBLOCK_TYPE_ID' => $this->iblockSectionID,
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/cars/#ELEMENT_ID#',
        ]);

        $arProps = [
            [
                'NAME' => 'Год выпуска',
                'CODE' => 'CAR_MANUFACT_DATE',
                'PROPERTY_TYPE' => 'N'
            ],
            [
                'NAME' => 'Водитель',
                'CODE' => 'CAR_DRIVER',
                'PROPERTY_TYPE' => 'E',
                'USER_TYPE' => 'EList',
                'LINK_IBLOCK_ID' => $driverId
            ],
            [
                'NAME' => 'Модель автомобиля',
                'CODE' => 'CAR_MODEL',
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'directory',
                'LIST_TYPE' => 'L',
                'USER_TYPE_SETTINGS' => array(
                    "size"=>"1", 
                    "width"=>"0", 
                    "group"=>"N", 
                    "multiple"=>"N", 
                    "TABLE_NAME"=>"hl_car_model"
                )
            ]
        ];
        if ($carsIblockId) {
            foreach ($arProps as $arProp) {
                $helper->Iblock()->addPropertyIfNotExists(
                    $carsIblockId,
                    $arProp
                );
            }

            $helper->AdminIblock()->buildElementForm($carsIblockId, [
                'Данные автомобиля' => [
                    'NAME' => 'Госномер',
                    'PROPERTY_CAR_MANUFACT_DATE',
                    'PROPERTY_CAR_DRIVER',
                    'PROPERTY_CAR_MODEL'
                ]
            ]);
        }

        return $carsIblockId;
    }

    /**
     * Добавить хайлоад-блок для хранения фактов "бронирования"
     * служебных автомобилей на определенное время.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     * @param int $carId Идентификатор инфоблока служебных автомобилей.
     * @return int Идентификатор созданного хайлоад-блока.
     */
    private function carBookingUp($helper, $carId)
    {
        $carBookingHlblockId = $helper->Hlblock()->saveHlBlock([
            'NAME' => $this->hlblockCodes['CAR_BOOKING']['NAME'],
            'TABLE_NAME' => 'hl_job_car_booking',
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $carBookingHlblockId,
            [
                [
                    'FIELD_NAME' => $this->hlblockCodes['CAR_BOOKING']['FIELDS']['START'],
                    'USER_TYPE_ID' => 'datetime',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Начало поездки', 'en' => 'Drive start'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Начало поездки', 'en' => 'Drive start'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Начало поездки', 'en' => 'Drive start'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['CAR_BOOKING']['FIELDS']['END'],
                    'USER_TYPE_ID' => 'datetime',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'Окончание поездки', 'en' => 'Drive end'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'Окончание поездки', 'en' => 'Drive end'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'Окончание поездки', 'en' => 'Drive end'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['CAR_BOOKING']['FIELDS']['USER'],
                    'USER_TYPE_ID' => 'integer',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'ID сотрудника', 'en' => 'Employee ID'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'ID сотрудника', 'en' => 'Employee ID'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'ID сотрудника', 'en' => 'Employee ID'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ],
                [
                    'FIELD_NAME' => $this->hlblockCodes['CAR_BOOKING']['FIELDS']['CAR'],
                    'USER_TYPE_ID' => 'integer',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => Array('ru' => 'ID служебного автомобиля', 'en' => 'Car ID'),
                    'LIST_COLUMN_LABEL' => Array('ru' => 'ID служебного автомобиля', 'en' => 'Car ID'),
                    'LIST_FILTER_LABEL' => Array('ru' => 'ID служебного автомобиля', 'en' => 'Car ID'),
                    'ERROR_MESSAGE' => Array('ru' => '', 'en' => ''),
                    'HELP_MESSAGE' => Array('ru' => '', 'en' => '')
                ]
            ]
        );

        return $carBookingHlblockId;
    }

    /**
     * Удалить хайлоад-блок марки автомобиля.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function carBrandDown($helper)
    {
        $carBrandsHlBlockId = $helper->Hlblock()->getHlblockIdIfExists(
            $this->hlblockCodes['BRANDS']['NAME']
        );

        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $carBrandsHlBlockId,
            [
                $this->hlblockCodes['BRANDS']['FIELDS']['NAME'],
                $this->hlblockCodes['BRANDS']['FIELDS']['LOGO'],
                $this->hlblockCodes['BRANDS']['FIELDS']['XML']
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
        $result = $helper->Iblock()->deleteIblockIfExists(
            $this->iblockCodes['DRIVERS']['CODE']
        );
    }

    /**
     * Удаление хайлоад-блока с категориями комфорта.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function comfortabilityDown($helper)
    {
        $combofrtabilityHlBlockId = $helper->Hlblock()->getHlblockIdIfExists(
            $this->hlblockCodes['COMFORTABILITY']['NAME']
        );
       
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $combofrtabilityHlBlockId,
            [
                $this->hlblockCodes['COMFORTABILITY']['FIELDS']['NAME'],
                $this->hlblockCodes['COMFORTABILITY']['FIELDS']['XML']
            ]
        );
        $helper->Hlblock()->deleteHlblock($combofrtabilityHlBlockId);
    }

    /**
     * Удаление хайлоад-блока с должностями.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function positionDown($helper)
    {
        $positionHlBlockId = $helper->Hlblock()->getHlblockIdIfExists(
            $this->hlblockCodes['POSITIONS']['NAME']
        );
       
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $positionHlBlockId,
            [
                $this->hlblockCodes['POSITIONS']['FIELDS']['NAME'],
                $this->hlblockCodes['POSITIONS']['FIELDS']['XML']
            ]
        );
        $helper->Hlblock()->deleteHlblock($positionHlBlockId);
    }

    /**
     * Удаление хайлоад-блока с моделями автомобилей.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function carModelDown($helper)
    {
        $carModelsHlBlockId = $helper->Hlblock()->getHlblockIdIfExists(
            $this->hlblockCodes['MODELS']['NAME']
        );

        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $carModelsHlBlockId,
            [
                $this->hlblockCodes['MODELS']['FIELDS']['NAME'],
                $this->hlblockCodes['MODELS']['FIELDS']['XML'],
                $this->hlblockCodes['MODELS']['FIELDS']['PHOTO'],
                $this->hlblockCodes['MODELS']['FIELDS']['BRAND'],
                $this->hlblockCodes['MODELS']['FIELDS']['COMFORTABILITY']
            ]
        );
        $helper->Hlblock()->deleteHlblock($carModelsHlBlockId);
    }
    
    /**
     * Удаление хайлоад-блока для сопоставления должностей и категорий комфорта.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function comfortabilityAvailabilityDown($helper)
    {
        $comfAvalHlBlockId = $helper->Hlblock()->getHlblockIdIfExists(
            $this->hlblockCodes['COMFORTABILITY_AVAILABLE']['NAME']
        );

        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $comfAvalHlBlockId,
            [
                $this->hlblockCodes['COMFORTABILITY_AVAILABLE']['FIELDS']['POSITION'],
                $this->hlblockCodes['COMFORTABILITY_AVAILABLE']['FIELDS']['CATEGORY']
            ]
        );
        $helper->Hlblock()->deleteHlblock($comfAvalHlBlockId);
    }

    /**
     * Удалить инфоблок служебных автомобилей.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function carDown($helper)
    {
        $result = $helper->Iblock()->deleteIblockIfExists(
            $this->iblockCodes['CARS']['CODE']
        );
    }

    /**
     * Удаление хайлоад-блока для бронирования служебных автомобилей.
     * @param HelperManager $helper Менеджер для выполнения действий миграции.
     */
    private function carBookingDown($helper)
    {
        $carBookingHlblockId = $helper->Hlblock()->getHlblockIdIfExists(
            $this->hlblockCodes['CAR_BOOKING']['NAME']
        );

        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
            'HLBLOCK_' . $carBookingHlblockId,
            [
                $this->hlblockCodes['CAR_BOOKING']['FIELDS']['START'],
                $this->hlblockCodes['CAR_BOOKING']['FIELDS']['END'],
                $this->hlblockCodes['CAR_BOOKING']['FIELDS']['USER'],
                $this->hlblockCodes['CAR_BOOKING']['FIELDS']['CAR']
            ]
        );
        $helper->Hlblock()->deleteHlblock($carBookingHlblockId);
    }
}
