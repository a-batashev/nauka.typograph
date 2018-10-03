<?php
global $MESS;

$langPath = str_replace("\\", "/", __FILE__);
$langPath = substr($langPath, 0, strlen($langPath) - strlen("/install/index.php"));
include(GetLangFileName($langPath . "/lang/", "/install/index.php"));

class nauka_typograph extends CModule {
	var $MODULE_ID = "nauka.typograph";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	public function __construct() {
		$arModuleVersion = array();
		
		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");
		
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		
		$this->MODULE_NAME = getMessage('NAUKA_TYPOGRAPH_MODULE_NAME');
		$this->MODULE_DESCRIPTION = getMessage('NAUKA_TYPOGRAPH_MODULE_DESCRIPTION');
		
		$this->PARTNER_NAME = "AA.Batashev";
		$this->PARTNER_URI = "https://npo-nauka.ru";
	}

	public function InstallDB($install_wizard = true) {
		RegisterModule($this->MODULE_ID);
		return true;
	}

	public function UnInstallDB($arParams = Array()) {
		COption::RemoveOption($this->MODULE_ID);
		UnRegisterModule($this->MODULE_ID);
		return true;
	}

	public function InstallFiles() {
		$path = str_replace("\\", "/", __DIR__);
		CopyDirFiles($path."/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/");
		CopyDirFiles($path."/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID);
		CopyDirFiles($path."/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/".$this->MODULE_ID);
		CopyDirFiles($path."/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID);
		
		return true;
	}
	
	public function UnInstallFiles() {
		$path = str_replace("\\", "/", __DIR__);
		DeleteDirFiles($path."/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFilesEx("/bitrix/tools/".$this->MODULE_ID);
		DeleteDirFilesEx("/bitrix/js/".$this->MODULE_ID);
		DeleteDirFilesEx("/bitrix/images/".$this->MODULE_ID);
		
		return true;
	}

	public function DoInstall() {
		RegisterModuleDependences("fileman", "OnBeforeHTMLEditorScriptRuns", $this->MODULE_ID, "CNaukaTypograph", "OnBeforeHTMLEditorScriptRunsHandler" );
		
		RegisterModuleDependences("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, "CNaukaTypograph", "OnBeforeIBlockElementAddOrUpdateHandler");
		RegisterModuleDependences("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, "CNaukaTypograph", "OnBeforeIBlockElementAddOrUpdateHandler");
		
		$this->InstallDB(false);
		$this->InstallFiles();
	}

	public function DoUninstall() {
		UnRegisterModuleDependences("fileman", "OnBeforeHTMLEditorScriptRuns", $this->MODULE_ID, "CNaukaTypograph", "OnBeforeHTMLEditorScriptRunsHandler" );
		
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, "CNaukaTypograph", "OnBeforeIBlockElementAddOrUpdateHandler");
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, "CNaukaTypograph", "OnBeforeIBlockElementAddOrUpdateHandler");
		
		$this->UnInstallDB();
		$this->UnInstallFiles();
	}
}