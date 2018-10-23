<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

if (!$USER->IsAdmin()) {
	return;
}

// Fatal errors only
error_reporting(E_ERROR);

if (CModule::IncludeModule("iblock")) {
	
	// Typograph elements of the selected iblocks
	if (is_array($_POST["auto_typograph_iblocks"])) {
		
		// Sanitize $_POST["auto_typograph_iblocks"]
		$auto_typograph_iblocks = array_filter($_POST["auto_typograph_iblocks"], function($id) { return ($id == intval($id) && $id > 0); });
		
		if ($auto_typograph_iblocks ) {
			
			$lastID = intval($_POST["lastID"]);
			$resElement = CIBlockElement::GetList(
				array("ID" => "ASC"),
				array("IBLOCK_ID" => $auto_typograph_iblocks, ">ID" => $lastID),
				false,
				false,
				array("IBLOCK_ID", "ID", "NAME", "PREVIEW_TEXT", "DETAIL_TEXT")
			);
			while ($arElement = $resElement->Fetch()) {
				$arUpdateElements[] = $arElement;
			}
			$elements_total = count($arUpdateElements);
			
			if ($elements_total === 0) {
				echo json_encode(array("LAST_ID" => -1));
			} elseif ($elements_total > 0 && CModule::IncludeModule("nauka.typograph")) {
				$el = new CIBlockElement;
				
				$endTime = time() + 3;
				foreach ($arUpdateElements as $arUpdateElement) {
					$arFields = array();
					$arFields["NAME"] = CNaukaTypograph::fastApply(
						$arUpdateElement["NAME"],
						array(
							'OptAlign.all' => 'off',
							'Text.paragraphs' => 'off',
							'Nobr.spaces_nobr_in_surname_abbr' => 'off',
							'Etc.unicode_convert' => 'on',
						)
					);
					foreach (array("PREVIEW_TEXT", "DETAIL_TEXT") as $FIELD) {
						$TEXT = CNaukaTypograph::fastApply($arUpdateElement[$FIELD]);
						if ($TEXT != $arUpdateElement[$FIELD]) {
							$arFields[$FIELD] = $TEXT;
							$arFields["{$FIELD}_TYPE"] = "html";
						}
					}
					if ($arFields) {
						if ($el->Update($arUpdateElement["ID"], $arFields, false, false)) {
							$elements_updated++;
						} else {
							$result["LAST_ERROR"][] = array("ID" => $arUpdateElement["ID"], "ERROR_TEXT" => $el->LAST_ERROR);
						}
					} else {
						$elements_updated++;
					}
					
					$newLastID = $arUpdateElement["ID"];
					if (time() > $endTime) {
						$bTimeout = true;
						break;
					}
				}
				
				if (!$bTimeout) {
					$newLastID = -1;
				}
				
				$result["LAST_ID"] = $newLastID;
				$result["UPDATED"] = $elements_updated;
				
				if ($lastID === 0) {
					$result["TOTAL"] = $elements_total;
				}
				echo json_encode($result);
				
			}
			
		}
		
	}
	
}
