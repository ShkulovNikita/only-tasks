<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?=$arResult["FORM_NOTE"]?>
<?if ($arResult["isFormNote"] != "Y")
{
	?>
	<div class="contact-form">
		<!--Вывод ошибок формы, если есть-->
		<? if ($arResult["isFormErrors"] == "Y"):?>
			<?=$arResult["FORM_ERRORS_TEXT"];?>
		<?endif;?>

		<div class="contact-form__head">
			<!--Вывести заголовок: "Связаться"-->
			<div class="contact-form__head-title">
				<?=$arResult["FORM_TITLE"]?>
			</div>
			<!--Описание формы-->
			<div class="contact-form__head-text">
				<?=$arResult["arForm"]["DESCRIPTION"]?>
			</div>
		</div>

		<!--Начало самой формы-->
		<?=$arResult["FORM_HEADER"]?>

		<!--Поля для ввода-->
		<div class="contact-form__form-inputs">
			<!--Перебрать вопросы формы-->
			<? 
				foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion) 
				{
					// medicine_message будет выведена в отдельном блоке div,
					// поэтому в contact-form__form-inputs игнорируется
					if ($FIELD_SID != "medicine_message") 
					{
						?>
						<!--Вывод блока с очередным полем ввода-->
						<div class="input contact-form__input">
							<label class="input__label" for="<?=$FIELD_SID?>">
								<!--Подпись для поля-->
								<div class="input__label-text">
									<?=$arQuestion["CAPTION"]?>*
								</div>
								<!--Поле для ввода значения-->
								<?=$arQuestion["HTML_CODE"]?>
								
								<?if (is_array($arResult["FORM_ERRORS"]) && array_key_exists($FIELD_SID, $arResult['FORM_ERRORS'])):?>
									<!--Если есть ошибка для данного поля, то вывести её-->
									<div class="input__notification">
										<?=htmlspecialcharsbx($arResult["FORM_ERRORS"][$FIELD_SID])?>
									</div>
								<?endif;?>
							</label>
						</div>
						<?
					}
				}
			?>
		</div>
		<!--Также найти среди полей формы поле для ввода сообщения-->
		<?
			foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion) 
			{
				if ($FIELD_SID == "medicine_message")
				{
					?>
					<div class="contact-form__form-message">
						<div class="input">
							<label class="input__label" for="medicine_message">
								<div class="input__label-text"><?=$arQuestion["CAPTION"]?></div>
								<?=$arQuestion["HTML_CODE"]?>
								<div class="input__notification"></div>
							</label>
						</div>
					</div>
					<?
				}
			}
		?>

		<div class="contact-form__bottom">
            <div class="contact-form__bottom-policy">Нажимая &laquo;Отправить&raquo;, Вы&nbsp;подтверждаете, что
                ознакомлены, полностью согласны и&nbsp;принимаете условия &laquo;Согласия на&nbsp;обработку персональных
                данных&raquo;.
            </div>

			<!--Кнопка отправки формы-->
			<input <?=(intval($arResult["F_RIGHT"]) < 10 ? "disabled=\"disabled\"" : "");?> type="submit" class="form-button contact-form__bottom-button" data-success="Отправлено" data-error="Ошибка отправки" name="web_form_submit" value="<?=htmlspecialcharsbx(trim($arResult["arForm"]["BUTTON"]) == '' ? GetMessage("FORM_ADD") : $arResult["arForm"]["BUTTON"]);?>" />
		</div>

		<?=$arResult["FORM_FOOTER"]?>
	</div>
<?
} //endif (isFormNote)