<?php
if (!$USER->IsAdmin()) { return; }

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

if ($REQUEST_METHOD == "POST" && strlen($Update) > 0 && check_bitrix_sessid()) {
	$auto_typograph_iblocks = array();
	if (is_array($_POST["auto_typograph_iblocks"])) {
		// Clean $_POST["auto_typograph_iblocks"]
		$auto_typograph_iblocks = array_filter($_POST["auto_typograph_iblocks"], function($id) { return ($id == intval($id) && $id > 0); });
	};
	
	// Set option
	COption::SetOptionString("nauka.typograph", "auto_typograph_iblocks", serialize($auto_typograph_iblocks), false);
};

if (CModule::IncludeModule("iblock")) {
	// Get IBlocks
	$resIBlock = CIBlock::GetList(array('ID' => 'ASC'), array('ACTIVE' => 'Y'));
	while ($arIBlock = $resIBlock->Fetch()) {
		$arIBlocksByType[$arIBlock["IBLOCK_TYPE_ID"]][$arIBlock["ID"]] = $arIBlock["NAME"];
	};
	
	// Get names for IBlock types
	foreach (array_keys($arIBlocksByType) as $type) {
		if ($arIBTypeName = CIBlockType::GetByIDLang($type, LANG))
			$arIBTypeNames[$type] = htmlspecialcharsex($arIBTypeName["NAME"]);
	};
};

$auto_typograph_iblocks = unserialize(COption::GetOptionString("nauka.typograph", "auto_typograph_iblocks"));
if (!is_array($auto_typograph_iblocks))
	$auto_typograph_iblocks = array();

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET"))
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();?>
<form method="post" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
<?$tabControl->BeginNextTab();?>
	<tr>
		<td colspan="2"><p><?=GetMessage("NAUKA_TYPOGRAPH_OPTIONS_AUTOIBLOCKS")?>:</p></td>
	</tr>
	<tr>
		<td colspan="2">
			<select id="auto_typograph_iblocks" name="auto_typograph_iblocks[]" multiple="multiple" size="10">
			<?if (count($arIBlocksByType) > 0) :?>
				<?foreach ($arIBlocksByType as $type => $arIblocks) :?>
					<optgroup label="<?=$arIBTypeNames[$type]?>">
					<?foreach ($arIblocks as $IBlockID => $IBlockName) :?>
						<option value="<?=$IBlockID?>" <?=(in_array($IBlockID, $auto_typograph_iblocks)) ? 'selected="selected"' : ''?>>
							<?=$IBlockName?>
						</option>
					<?endforeach;?>
					</optgroup>
				<?endforeach;?>
			<?endif;?>
			</select>
		</td>
	</tr>
	
<?$tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save" />
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
<br>

<?
$aTabs = array(
	array("DIV" => "edit21", "TAB" => GetMessage("NAUKA_TYPOGRAPH_OPTIONS_APPLY_TO_IBLOCKS_TITLE"), "ICON" => "", "TITLE" => GetMessage("NAUKA_TYPOGRAPH_OPTIONS_APPLY_TO_IBLOCKS")),
);
$tabControl = new CAdminTabControl("tabControl2", $aTabs);
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<tr>
	<td valign="top">
		<div id="typograph_progressbar">
			<div class="adm-info-message-wrap adm-info-message-gray">
				<div class="adm-info-message">
					<div class="adm-info-message-title" style="display: none;">
						<?=GetMessage('NAUKA_TYPOGRAPH_OPTIONS_PROGRESS_TITLE', array(
							'#ELEMENTS_UPDATED#' => '<span id="adm-info-message-title-updated"></span>', 
							'#ELEMENTS_TOTAL#' => '<span id="adm-info-message-title-total"></span>')
						)?>
					</div>
					<div class="adm-progress-bar-outer" style="width: 500px;">
						<div class="adm-progress-bar-inner" style="width: 0%;">
							<div class="adm-progress-bar-inner-text" style="width: 500px;">0%</div>
						</div>
					</div>
					<div class="adm-info-message-buttons"></div>
				</div>
			</div>
			<style>
				#bx-admin-prefix .adm-progress-bar-outer { height: 31px; padding: 2px 2px 0; }
				#bx-admin-prefix .adm-progress-bar-inner { position: static; }
				#bx-admin-prefix .adm-progress-bar-inner-text { text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5); }
			</style>
		</div>
		
		<p>
			<input type="button" id="typograph_apply_start" value="<?=GetMessage('NAUKA_TYPOGRAPH_OPTIONS_START')?>" />
			<input type="button" id="typograph_apply_stop" value="<?=GetMessage('NAUKA_TYPOGRAPH_OPTIONS_STOP')?>" style="display: none;" />
			<input type="button" id="typograph_apply_continue" value="<?=GetMessage('NAUKA_TYPOGRAPH_OPTIONS_CONTINUE')?>" style="display: none;" />
		</p>
		
		<div id="typograph_error_message" style="display: none;">
		<?=CAdminMessage::ShowMessage(array(
				"MESSAGE" => GetMessage("NAUKA_TYPOGRAPH_OPTIONS_ERROR"), 
				"DETAILS" => '<div id="adm-info-message-text"></div>',
				"HTML" => true
			));?>
		</div>
		
		<script type="text/javascript">
			BX.ready(function() {
				
				// Hide progressbar title
				var objProgressbarTitle = BX.findChild(BX('typograph_progressbar'), { 'class': 'adm-info-message-title' }, true);
				
				var LAST_ID = 0;
				
				// Progress
				var elements_updated = elements_total = elements_percents = 0;
				var objUpdated = BX('adm-info-message-title-updated');
				var objTotal = BX('adm-info-message-title-total');
				var objBarPercents = BX.findChild(BX('typograph_progressbar'), { 'class': 'adm-progress-bar-inner' }, true);
				var objPercents = BX.findChild(BX('typograph_progressbar'), { 'class': 'adm-progress-bar-inner-text' }, true);
				
				// Error message
				var objErrorMsg = BX.findChild(BX('typograph_error_message'), { 'class': 'adm-info-message' }, true);
				var objErrorMsgText = BX('adm-info-message-text');
				
				// Start
				BX.bind(BX('typograph_apply_start'), 'click', function() {
					elements_updated = elements_total = 0;
					objUpdated.innerHTML = objTotal.innerHTML = 0;
					this.disabled = true;
					BX('typograph_apply_stop').style.display = '';
					BX('typograph_error_message').style.display = 'none';
					objErrorMsgText.innerHTML = '';
					objProgressbarTitle.style.display = '';
					objPercents.innerHTML = objBarPercents.style.width = '0%';
					typographApplyStart(0);
				});
			
				// Continue
				BX.bind(BX('typograph_apply_continue'), 'click', function() {
					this.style.display = 'none';
					BX('typograph_apply_stop').style.display = '';
					BX('typograph_apply_start').disabled = true;
					typographApplyStart(LAST_ID);
				});
			
				// Stop
				BX.bind(BX('typograph_apply_stop'), 'click', typographApplyStop);
			
				function typographApplyStart(lastID) {
					BX.showWait();
					
					if (!BX('typograph_apply_start').disabled)
						return;
					
					var auto_typograph_iblocks = [];
					BX.findChild(BX('auto_typograph_iblocks'), { 'property': 'selected' }, true, true).forEach(
						function(element) {
							auto_typograph_iblocks.push(element.value); 
						}
					);
					if (auto_typograph_iblocks.length == 0) {
						BX.closeWait();
						typographApplyStop();
						return;
					};
					
					var data = {
						'auto_typograph_iblocks': auto_typograph_iblocks,
						'lastID': lastID 
					};
					
					return BX.ajax({
						'method': 'POST',
						'dataType': 'json',
						'url': '/bitrix/admin/nauka_typograph_apply.php',
						'data': data,
						'timeout': 30,
						'onsuccess': typographApplySuccess,
						'onfailure': typographApplyFail
					});
					
				};
			
				function typographApplyStop() {
					BX('typograph_apply_stop').style.display = 'none';
					BX('typograph_apply_start').disabled = false;
				};
			
				function typographApplySuccess(response) {
					BX.closeWait();
					
					// Stopped
					if (!BX('typograph_apply_start').disabled)
						return;
					
					// No response
					if (!response) 
						return typographApplyFail();
					
					// Progress title
					if (response.UPDATED > 0) {
						elements_updated += response.UPDATED;
						objUpdated.innerHTML = elements_updated;
					};
					if (response.TOTAL > 0) {
						objTotal.innerHTML = elements_total = response.TOTAL;
					};
					// Progress bar
					if (elements_updated > 0 && elements_total > 0) {
						strPercents = Math.floor(elements_updated / elements_total * 100) + '%';
						objBarPercents.style.width = objPercents.innerHTML = strPercents;
					};
					
					// Not updated
					if (response.hasOwnProperty("LAST_ERROR")) {
						
						if (objErrorMsgText.innerHTML == '')
							objErrorMsgText.innerHTML = '<?=GetMessage("NAUKA_TYPOGRAPH_OPTIONS_ERROR_UPDATES")?>';
						
						for (key in response.LAST_ERROR) {
							var LAST_ERROR = response.LAST_ERROR[key];
							objErrorMsgText.innerHTML += 'ID: ' + LAST_ERROR.ID + ' "' + LAST_ERROR.ERROR_TEXT + '";<br>';
						};
						
						BX('typograph_error_message').style.display = '';
					};
					
					// Finished
					if (response.LAST_ID == (-1)) {
						typographApplyStop();
						return;
					};
					
					// Next step
					LAST_ID = response.LAST_ID;
					typographApplyStart(response.LAST_ID);
				};
			
				function typographApplyFail(response) {
					BX.closeWait();
					objErrorMsgText.innerHTML += '<?=GetMessage("NAUKA_TYPOGRAPH_OPTIONS_ERROR_DETAILS")?><br>';
					BX('typograph_error_message').style.display = '';
					BX('typograph_apply_continue').style.display = '';
					typographApplyStop();
				};
			
			});
		</script>
	</td>
</tr>
<?php $tabControl->End();?>