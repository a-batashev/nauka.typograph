<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (CModule::IncludeModule('nauka.typograph')) {
	echo CNaukaTypograph::fastApply($_REQUEST['text']);
};
?>