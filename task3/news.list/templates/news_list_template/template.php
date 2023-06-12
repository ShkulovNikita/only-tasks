<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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

<div id="barba-wrapper">
    <!-- Пройти циклом по всем разделам, чтобы найти их новости -->
    <?php foreach ($arResult["SECTIONS"] as $arSection) { ?> 
        <!-- Каждый раздел отображается своим списком -->
        <div class="article-list">
            <!-- Вывести название раздела -->
            <div class="article-card__title"><?=$arSection["NAME"]?></div>

            <!-- Перебрать все новости для поиска совпадения их разделов с текущим -->
            <?php foreach ($arResult["ITEMS"] as $arItem) { 
                if ($arItem["IBLOCK_SECTION_ID"] == $arSection["ID"]) { ?>
                    <!-- Если идентификаторы разделов совпали, то вывести новость в данном блоке -->
			
                    <!-- Блок-ссылка на подробную новость -->
                    <a class="article-item article-list__item" 
                        href="<?php echo $arItem["DETAIL_PAGE_URL"] ?>"
                        data-anim="anim-3">
                        <!-- Установка фонового изображения -->
                        <div class="article-item__background">
                            <img src="<?= $arItem["PREVIEW_PICTURE"]["SRC"] ?>"
                                data-src="xxxHTMLLINKxxx0.39186223192351520.41491856731872767xxx"
                                alt="<?php echo $arItem["NAME"] ?>"/>
                        </div>
                        <!-- Текстовая часть блока -->
                        <div class="article-item__wrapper">
                            <!-- Заголовок -->
                            <div class="article-item__title"><?php echo $arItem["NAME"] ?></div>
                            <!-- Текст анонса -->
                            <div class="article-item__content">
                                <?php if ($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]) { ?>
                                    <?php echo $arItem["PREVIEW_TEXT"]; ?>
                                <?php } ?>
                            </div>
                        </div>
                    </a>
                <?php } ?>
            <?php } ?>
        </div>
    <?php } ?>
</div>
