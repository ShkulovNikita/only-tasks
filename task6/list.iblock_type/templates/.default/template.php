<?php 
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<!-- Контейнер для списка инфоблоков. -->
<div class="news-list">
    <!-- Вывод постранички, если она должна быть сверху. -->
    <?php if ($arParams["DISPLAY_TOP_PAGER"]) { ?>
        <?=$arResult["NAV_STRING"]?><br />
    <?php } ?>
    <!-- Перебор всех инфоблоков для вывода. -->
    <?php foreach ($arResult["ITEMS"] as $arItem) { ?>
        <!-- Добавить кнопки для редактирования и удаления текущего элемента. -->
        <?php
            $this->AddEditAction(
                $arItem['ID'], 
                $arItem['EDIT_LINK'], 
                CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT")
            );
            $this->AddDeleteAction(
                $arItem['ID'], 
                $arItem['DELETE_LINK'], 
                CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), 
                array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM'))
            );
        ?>
        <!-- Текущий элемент инфоблока. -->
        <p class="news-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
            <!-- Вывод изображения. -->
            <?php if ($arParams["DISPLAY_PICTURE"] != "N" && is_array($arItem["PREVIEW_PICTURE"])) { ?>
                <!-- В изображение встраивается ссылка. -->
                <?php if (!$arParams["HIDE_LINK_WHEN_NO_DETAIL"] || ($arItem["DETAIL_TEXT"] && $arResult["USER_HAVE_ACCESS"])) { ?>
                    <a href="<?=$arItem["DETAIL_PAGE_URL"]?>">
                        <img
                            class="preview_picture"
                            border="0"
                            src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"
                            width="<?=$arItem["PREVIEW_PICTURE"]["WIDTH"]?>"
                            height="<?=$arItem["PREVIEW_PICTURE"]["HEIGHT"]?>"
                            alt="<?=$arItem["PREVIEW_PICTURE"]["ALT"]?>"
                            title="<?=$arItem["PREVIEW_PICTURE"]["TITLE"]?>"
                            style="float:left"
                        />
                    </a>
                <!-- Вывод изображения без ссылки. -->
                <?php } else { ?>
                    <img
                        class="preview_picture"
                        border="0"
                        src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"
                        width="<?=$arItem["PREVIEW_PICTURE"]["WIDTH"]?>"
                        height="<?=$arItem["PREVIEW_PICTURE"]["HEIGHT"]?>"
                        alt="<?=$arItem["PREVIEW_PICTURE"]["ALT"]?>"
                        title="<?=$arItem["PREVIEW_PICTURE"]["TITLE"]?>"
                        style="float:left"
                    />
                <?php } ?>
            <?php } ?>
            <!-- Отображение даты. -->
            <?php if ($arParams["DISPLAY_DATE"] != "N" && $arItem["DISPLAY_ACTIVE_FROM"]) { ?>
                <span class="news-date-time"><?php echo $arItem["DISPLAY_ACTIVE_FROM"]?></span>
            <?php } ?>
            <!-- Отображение имени. -->
            <?php if ($arParams["DISPLAY_NAME"] != "N" && $arItem["NAME"]) { ?>
                <!-- Встроить в имя ссылку. -->
                <?php if (!$arParams["HIDE_LINK_WHEN_NO_DETAIL"] || ($arItem["DETAIL_TEXT"] && $arResult["USER_HAVE_ACCESS"])) { ?>
                    <a href="<?echo $arItem["DETAIL_PAGE_URL"]?>"><b><?php echo $arItem["NAME"]?></b></a><br />
                <!-- Вывести имя без ссылки. -->
                <?php } else { ?>
                    <b><?php echo $arItem["NAME"]?></b><br />
                <?php } ?>
            <?php } ?>
            <!-- Отображение текста анонса. -->
            <?php if ($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]) { ?>
                <?php echo $arItem["PREVIEW_TEXT"];?>
            <?php } ?>
            <!-- Добавление clear, если ранее была выведена картинка анонса. -->
            <?php if ($arParams["DISPLAY_PICTURE"] != "N" && is_array($arItem["PREVIEW_PICTURE"])) { ?>
                <div style="clear:both"></div>
            <?php } ?>
            <!-- Вывод выбранных полей. -->
            <?php foreach ($arItem["FIELDS"] as $code => $value) { ?>
                <small>
                    <?=GetMessage("IBLOCK_FIELD_".$code)?>:&nbsp;<?=$value;?>
                </small><br />
            <?php } ?>
            <!-- Вывод выбранных свойств. -->
            <?php foreach ($arItem["DISPLAY_PROPERTIES"] as $pid => $arProperty) { ?>
                <small>
                    <?=$arProperty["NAME"]?>:&nbsp;
                    <?php if (is_array($arProperty["DISPLAY_VALUE"])) { ?>
                        <?=implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]);?>
                    <?php } else { ?>
                        <?=$arProperty["DISPLAY_VALUE"];?>
                    <?php } ?>
                </small><br />
            <?php } ?>
        </p>
    <?php } ?>
    <!-- Добавить постраничку, если она должна быть внизу. -->
    <?php if ($arParams["DISPLAY_BOTTOM_PAGER"]) { ?>
        <br /><?=$arResult["NAV_STRING"]?>
    <? } ?>
    <br><br>
    <?php echo "Входные параметры:"; ?>
    <br>
    <?php print_r($arParams); ?>
    <br><br>
    <?php echo "arResult:"; ?>
    <br>
    <?php print_r($arResult); ?>
</div>
