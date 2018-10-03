<?php
if (!check_bitrix_sessid())
	return;

IncludeModuleLangFile(__FILE__);
echo CAdminMessage::ShowNote(getMessage('NAUKA_TYPOGRAPH_INSTALL_SUCCESS'));
?>