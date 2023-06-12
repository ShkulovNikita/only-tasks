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
<div class="article-card">
	<!-- Заголовок статьи -->
    <?php if ($arParams["DISPLAY_NAME"]!="N" && $arResult["NAME"]):?>
        <div class="article-card__title"><?=$arResult["NAME"]?></div>
    <?php endif?>
	<!-- Дата публикации -->
    <?php if ($arParams["DISPLAY_DATE"]!="N" && $arResult["DISPLAY_ACTIVE_FROM"]):?>
        <div class="article-card__date"><?=$arResult["DISPLAY_ACTIVE_FROM"]?></div>
    <?php endif?>
    <div class="article-card__content">
        <!-- Изображение статьи -->
        <div class="article-card__image sticky">
            <?php if ($arParams["DISPLAY_PICTURE"]!="N" && is_array($arResult["DETAIL_PICTURE"])):?>
                <img src="<?=$arResult["DETAIL_PICTURE"]["SRC"]?>" alt="<?=$arResult["DETAIL_PICTURE"]["ALT"]?>" data-object-fit="cover"/>
            <?php endif?>
        </div>
        <div class="article-card__text">
            <!-- Полный текст новости -->
            <div class="block-content" data-anim="anim-3">
                <?php if ($arResult["DETAIL_TEXT"] <> ''):?>
                    <?=$arResult["DETAIL_TEXT"]?>
                <?php else:?>
                    <?=$arResult["PREVIEW_TEXT"]?>
                <?php endif?>
            </div>
            <a class="article-card__button" href="<?=$arResult["BACK_NEWS_URL"]?>">Назад к новостям</a></div>
        </div>
    </div>
</div>
