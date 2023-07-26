<?php

namespace Sprint\Migration;


class Version20230726141301 extends Version
{
    protected $description = "Создать инфоблоки и highload-блоки для компонента выбора служебного автомобиля.";

    protected $moduleVersion = "4.2.4";

    public function up()
    {
        $helper = $this->getHelperManager();
        $carBrandsHlBlockId = self::carBrandUp($helper);
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        self::carBrandDown($helper);
    }

    /**
     * Добавить хайлоад-блок для марки автомобиля.
     * @return int Идентификатор добавленного хайлоад-блока.
     */
    private static function carBrandUp($helper)
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
     * Удалить хайлоад-блок марки автомобиля.
     */
    private static function carBrandDown($helper)
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
