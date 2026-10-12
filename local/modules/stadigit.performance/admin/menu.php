<?php

use Bitrix\Main\Localization\Loc;

if ($APPLICATION->GetGroupRight('stadigit.performance') === 'D') {
    return false;
}

$aMenu = [
    'parent_menu' => 'global_menu_services',
    'section' => 'stadigit_performance',
    'sort' => 80,
    'text' => 'Производительность сайта',
    'title' => 'Диагностика производительности Bitrix',
    'url' => 'performance_dashboard.php?lang=' . LANGUAGE_ID,
    'icon' => 'sys_menu_icon',
    'page_icon' => 'sys_page_icon',
    'items_id' => 'menu_stadigit_performance',
];

return $aMenu;
