<?
echo '<p>' . htmlspecialcharsbx(GetMessage('STADIGIT_PERFORMANCE_UNINSTALL_SUCCESS')) . '</p>';
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="submit" value="<?= GetMessage('MOD_BACK') ?>">
</form>
