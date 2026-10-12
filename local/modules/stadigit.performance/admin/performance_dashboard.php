<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Stadigit\Performance\Diagnostics;
use Stadigit\Performance\CacheManager;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещён');
}

if (!Loader::includeModule('stadigit.performance')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/stadigit.performance/include.php';
}

$message = '';
$messageType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    try {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'clear_cache') {
            CacheManager::clearCache();
            $message = 'Кеш файлов очищен.';
        } elseif ($action === 'clear_managed_cache') {
            CacheManager::clearManagedCache();
            $message = 'Managed cache очищен.';
        } else {
            throw new RuntimeException('Неизвестное действие.');
        }

        AddMessage2Log([
            'module' => 'stadigit.performance',
            'action' => $action,
            'user_id' => (int)$USER->GetID(),
            'time' => date('c'),
        ], 'stadigit.performance');
    } catch (Throwable $exception) {
        $message = 'Операция не выполнена: ' . $exception->getMessage();
        $messageType = 'error';
    }
}

$report = Diagnostics::run();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>
<style>
.performance-wrap { max-width: 1200px; }
.performance-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin:18px 0; }
.performance-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; margin:18px 0; }
.performance-card { background:#fff; border:1px solid #dce1e6; border-radius:8px; padding:16px; }
.performance-card strong { display:block; font-size:24px; margin-bottom:5px; }
.performance-table { width:100%; border-collapse:collapse; background:#fff; }
.performance-table th,.performance-table td { padding:12px; border-bottom:1px solid #e8ebef; text-align:left; vertical-align:top; }
.performance-table th { background:#f4f6f8; }
.status-ok { color:#218739; font-weight:700; }
.status-warning { color:#b76a00; font-weight:700; }
.performance-actions { display:flex; gap:10px; flex-wrap:wrap; margin:18px 0; }
.performance-note { color:#6b7280; font-size:12px; }
@media(max-width:700px) { .performance-table th:nth-child(4),.performance-table td:nth-child(4) { display:none; } }
</style>

<div class="performance-wrap">
    <div class="performance-header">
        <div>
            <h1>Производительность сайта</h1>
            <p>Снимок состояния PHP, базы данных и каталогов кеша.</p>
        </div>
        <a class="adm-btn" href="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam('', ['refresh'])) ?>">Обновить диагностику</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="<?= $messageType === 'error' ? 'adm-info-message-red' : 'adm-info-message-green' ?>">
            <?= htmlspecialcharsbx($message) ?>
        </div>
    <?php endif; ?>

    <div class="performance-cards">
        <div class="performance-card"><strong><?= (int)$report['summary']['total'] ?></strong>Проверок</div>
        <div class="performance-card"><strong><?= (int)$report['summary']['passed'] ?></strong>Успешно</div>
        <div class="performance-card"><strong><?= (int)$report['summary']['warnings'] ?></strong>Требуют внимания</div>
        <div class="performance-card"><strong><?= htmlspecialcharsbx($report['php_version']) ?></strong>Версия PHP</div>
    </div>

    <h2>Результаты проверок</h2>
    <table class="performance-table">
        <thead>
        <tr><th>Проверка</th><th>Статус</th><th>Значение</th><th>Рекомендация</th></tr>
        </thead>
        <tbody>
        <?php foreach ($report['checks'] as $check): ?>
            <tr>
                <td><?= htmlspecialcharsbx($check['name']) ?></td>
                <td class="status-<?= htmlspecialcharsbx($check['status']) ?>">
                    <?= $check['status'] === 'ok' ? 'OK' : 'Внимание' ?>
                </td>
                <td><?= htmlspecialcharsbx($check['value']) ?></td>
                <td><?= htmlspecialcharsbx($check['recommendation']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Управление кешем</h2>
    <p>Очистка кеша может временно увеличить нагрузку на сайт, поскольку кеш будет сформирован повторно. Выполняйте операции в период низкой нагрузки.</p>
    <div class="performance-actions">
        <form method="post" onsubmit="return confirm('Очистить файловый кеш? На время повторного формирования кеша нагрузка может вырасти.');">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="action" value="clear_cache">
            <button class="adm-btn" type="submit">Очистить файловый кеш</button>
        </form>
        <form method="post" onsubmit="return confirm('Очистить managed cache? Некоторые данные будут сформированы заново.');">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="action" value="clear_managed_cache">
            <button class="adm-btn" type="submit">Очистить managed cache</button>
        </form>
    </div>

    <p class="performance-note">
        Сформировано: <?= htmlspecialcharsbx($report['generated_at']) ?>.
        Проверка SQL выполняет только SELECT 1. Секреты подключения не выводятся.
    </p>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>
