<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещён');
}

if (!Loader::includeModule('stadigit.performance')) {
    // During first setup the module may not yet be loaded through autoload.
}

$moduleId = 'stadigit.performance';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    Option::set($moduleId, 'show_server_software', isset($_POST['show_server_software']) ? 'Y' : 'N');
    LocalRedirect($APPLICATION->GetCurPageParam('saved=Y', ['saved']));
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Основные настройки',
        'TITLE' => 'Настройки диагностики производительности',
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);
$tabControl->Begin();
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%">Показывать информацию о веб-сервере</td>
        <td>
            <input type="checkbox" name="show_server_software" value="Y"
                <?= \Bitrix\Main\Config\Option::get($moduleId, 'show_server_software', 'Y') === 'Y' ? 'checked' : '' ?>>
        </td>
    </tr>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" class="adm-btn-save" value="Сохранить">
</form>
<?php
$tabControl->End();
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
