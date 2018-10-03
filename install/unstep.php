<?php
if (!check_bitrix_sessid())
	return;

echo CAdminMessage::ShowNote(getMessage('NAUKA_TYPOGRAPH_UNINSTALL_SUCCESS'));
?>