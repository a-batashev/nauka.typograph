<?
global $MESS, $DOCUMENT_ROOT;

CModule::AddAutoloadClasses('nauka.typograph', array('EMTypograph' => 'lib/EMT.php'));

class CNaukaTypograph {

	function OnBeforeHTMLEditorScriptRunsHandler() {
		CJSCore::RegisterExt(
			'nauka_typograph',
			array(
				'js' => '/bitrix/js/nauka.typograph/nauka_typograph.js',
				'rel' => array('ajax')
			)
		);
		CJSCore::Init('nauka_typograph');
	}

	function OnBeforeIBlockElementAddOrUpdateHandler(&$arFields) {
		$auto_typograph_iblocks = unserialize(COption::GetOptionString("nauka.typograph", "auto_typograph_iblocks"));
		if (in_array($arFields["IBLOCK_ID"], $auto_typograph_iblocks)) {
			if (CModule::IncludeModule('nauka.typograph')) {
				$arFields["NAME"] = self::fastApply(
					$arFields["NAME"], 
					array(
						'OptAlign.all' => 'off', 
						'Text.paragraphs' => 'off', 
						'Nobr.spaces_nobr_in_surname_abbr' => 'off', 
						'Etc.unicode_convert' => 'on', 
					)
				);
				foreach (array("PREVIEW_TEXT", "DETAIL_TEXT") as $FIELD) {
					$TEXT = self::fastApply($arFields[$FIELD]);
					$arFields[$FIELD] = $TEXT;
					$arFields["{$FIELD}_TYPE"] = 'html';
				}
			}
		}
	}

	public static function fastApply($text, $options = array()) {
		if (
			$text == ''
			|| strpos($text, '<!--askaron.include') !== false // Fix for Askaron Include Module
			|| strpos($text, ';base64,') !== false // Typograph hangs on base64 images, so exclude it
		) {
			return $text;
		}
		
		// Options by default
		if (!is_array($options) || $options === array()) {
			$options = array(
				'OptAlign.all'   => 'off', // Disable "Optical align"
				'Text.breakline' => 'off', // Disable "Text auto-breakline"
				//'Symbol.arrows_symbols' => 'off', // Disable "Arrows to symbols"
				//'Number.thinsp_between_number_triads' => 'off', // Disable "Numbers triads delimiters"
			);
		}
		
		$typograph = new EMTypograph();
		$new_text = $typograph->fast_apply($text, $options);
		
		if ($new_text == '') {
			$new_text = $text;
		}
		
		return $new_text;
	}

}
?>