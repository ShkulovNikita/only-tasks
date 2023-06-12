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
    <div class="article-list">
		<!--Цикл для вывода всех статей-->
		<? foreach ($arResult["ITEMS"] as $arItem) : ?>
			<!--Добавление возможности редактирования и удаления элемента-->
			<?
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			?>

			<!--Код одного выводимого блока-->
			
			<!--Ссылка на подробную новость-->
			<a class="article-item article-list__item" 
				href="<? echo $arItem["DETAIL_PAGE_URL"] ?>"
				data-anim="anim-3">
				<!--Установка фонового изображения-->
				<div class="article-item__background">
					<img src="<?= $arItem["PREVIEW_PICTURE"]["SRC"] ?>"
						data-src="xxxHTMLLINKxxx0.39186223192351520.41491856731872767xxx"
						alt="<? echo $arItem["NAME"] ?>"/>
				</div>
				<!--Текстовая часть блока-->
				<div class="article-item__wrapper">
					<!--Заголовок-->
					<div class="article-item__title"><? echo $arItem["NAME"] ?></div>
					<!--Текст анонса-->
					<div class="article-item__content">
						<? if ($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]): ?>
    						<? echo $arItem["PREVIEW_TEXT"]; ?>
						<? endif; ?>
					</div>
				</div>
			</a>
		<? endforeach; ?>
	</div>
</div>
