<?php

CModule::AddAutoloadClasses(
	'nauka.typograph',
	['EMTypograph' => 'lib/EMT.php']
);

class CNaukaTypograph
{
	public static function OnBeforeHTMLEditorScriptRunsHandler()
	{
		CJSCore::RegisterExt(
			'nauka_typograph',
			array(
				'js' => '/bitrix/js/nauka.typograph/nauka_typograph.js',
				'lang' => '/bitrix/modules/nauka.typograph/lang/ru/install/js/nauka_typograph.php',
				'rel' => ['ajax']
			)
		);
		CJSCore::Init('nauka_typograph');
	}

	public static function OnBeforeIBlockElementAddOrUpdateHandler(&$arFields)
	{
		$auto_typograph_iblocks = unserialize(COption::GetOptionString('nauka.typograph', 'auto_typograph_iblocks'));
		if (is_array($auto_typograph_iblocks) && in_array($arFields["IBLOCK_ID"], $auto_typograph_iblocks) && CModule::IncludeModule('nauka.typograph')) {
			$arFields["NAME"] = self::fastApply(
				$arFields["NAME"],
				array(
					'OptAlign.all' => 'off',
					'Text.paragraphs' => 'off',
					'Nobr.spaces_nobr_in_surname_abbr' => 'off',
					'Etc.unicode_convert' => 'on',
				)
			);
			foreach (['PREVIEW_TEXT', 'DETAIL_TEXT'] as $FIELD) {
				$TEXT = self::fastApply($arFields[$FIELD]);
				$arFields[$FIELD] = $TEXT;
				$arFields["{$FIELD}_TYPE"] = 'html';
			}
		}
	}

	/**
	* Wrapper for EMTypograph::fast_apply()
	*
	* Set excludes and options by default
	*
	* @param string $text
	* @param array $options
	* @return string
	*/
	public static function fastApply($text, $options = [])
	{
		// Excludes
		if (
			$text == ''
			|| strpos($text, '<!--askaron.include') !== false // Fix for Askaron Include Module
			|| strpos($text, ';base64,') !== false // Typograph hangs on base64 images, so exclude it
		) {
			return $text;
		}

		// Options by default
		if (!is_array($options) || $options === []) {
			$options = array(
				'OptAlign.all'   => 'off', // Disable "Optical align"
				'Text.breakline' => 'off', // Disable "Text auto-breakline"
				//'Symbol.arrows_symbols' => 'off', // Disable "Arrows to symbols"
				//'Number.thinsp_between_number_triads' => 'off', // Disable "Numbers triads delimiters"
				'Number.numeric_sub' => 'off', // Disable "Numeric sub"
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
