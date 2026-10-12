<?php

namespace Stadigit\Performance;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

final class Diagnostics
{
    public static function run(): array
    {
        $checks = [];

        $checks[] = self::check(
            'PHP version',
            version_compare(PHP_VERSION, '8.1.0', '>='),
            'PHP ' . PHP_VERSION,
            'Используйте поддерживаемую версию PHP не ниже 8.1.'
        );

        $requiredExtensions = ['json', 'mbstring', 'curl', 'mysqli'];
        $missing = [];

        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        $checks[] = self::check(
            'PHP extensions',
            $missing === [],
            $missing ? 'Не найдены: ' . implode(', ', $missing) : 'Все основные расширения доступны',
            'Установите недостающие PHP-расширения и перезапустите PHP-FPM/Apache.'
        );

        $opcacheEnabled = function_exists('opcache_get_status')
            ? @opcache_get_status(false)
            : false;

        $checks[] = self::check(
            'OPcache',
            is_array($opcacheEnabled) && !empty($opcacheEnabled['opcache_enabled']),
            is_array($opcacheEnabled) && !empty($opcacheEnabled['opcache_enabled'])
                ? 'Включён'
                : 'Не активен или недоступен для текущего PHP SAPI',
            'Для production включите OPcache в конфигурации PHP и проверьте настройки PHP-FPM.'
        );

        $memoryLimit = (string)ini_get('memory_limit');
        $memoryBytes = self::iniBytes($memoryLimit);
        $checks[] = self::check(
            'PHP memory_limit',
            $memoryBytes === -1 || $memoryBytes >= 256 * 1024 * 1024,
            $memoryLimit,
            'Для тяжёлых административных операций обычно требуется не менее 256M; ориентируйтесь на профиль нагрузки.'
        );

        $maxExecution = (string)ini_get('max_execution_time');
        $checks[] = self::check(
            'max_execution_time',
            $maxExecution === '0' || (int)$maxExecution >= 30,
            $maxExecution . ' сек.',
            'Проверьте лимит времени для длительных операций. Не отключайте лимиты без оценки рисков.'
        );

        $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $cachePath = $documentRoot . '/bitrix/cache';
        $managedCachePath = $documentRoot . '/bitrix/managed_cache';

        $checks[] = self::check(
            'Кеш файлов',
            is_dir($cachePath) && is_writable($cachePath),
            is_dir($cachePath) ? (is_writable($cachePath) ? 'Каталог доступен для записи' : 'Нет прав на запись') : 'Каталог не найден',
            'Проверьте владельца и права каталога /bitrix/cache для пользователя веб-сервера.'
        );

        $checks[] = self::check(
            'Managed cache',
            is_dir($managedCachePath) && is_writable($managedCachePath),
            is_dir($managedCachePath) ? (is_writable($managedCachePath) ? 'Каталог доступен для записи' : 'Нет прав на запись') : 'Каталог не найден',
            'Проверьте владельца и права каталога /bitrix/managed_cache.'
        );

        $dbResult = self::checkDatabase();
        $checks[] = $dbResult;

        $checks[] = self::check(
            'Bitrix main module',
            Loader::includeModule('main'),
            Loader::includeModule('main') ? 'Модуль main загружен' : 'Модуль main не загружен',
            'Проверьте запуск диагностики из административной части Bitrix.'
        );

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'server_software' => (string)($_SERVER['SERVER_SOFTWARE'] ?? 'Не определён'),
            'sapi' => PHP_SAPI,
            'memory_limit' => $memoryLimit,
            'max_execution_time' => $maxExecution,
            'checks' => $checks,
            'summary' => [
                'total' => count($checks),
                'passed' => count(array_filter($checks, static fn($item) => $item['status'] === 'ok')),
                'warnings' => count(array_filter($checks, static fn($item) => $item['status'] !== 'ok')),
            ],
        ];
    }

    private static function checkDatabase(): array
    {
        try {
            $connection = Application::getConnection();
            $start = microtime(true);
            $connection->query('SELECT 1')->fetch();
            $elapsedMs = round((microtime(true) - $start) * 1000, 2);

            return self::check(
                'Database connectivity',
                true,
                'SELECT 1 выполнен за ' . $elapsedMs . ' мс',
                'Если время стабильно высокое, проверьте нагрузку БД, сеть и медленные запросы.'
            );
        } catch (\Throwable $exception) {
            return self::check(
                'Database connectivity',
                false,
                'Запрос проверки не выполнен',
                'Проверьте подключение к БД и журнал ошибок Bitrix/PHP.'
            );
        }
    }

    private static function check(string $name, bool $ok, string $value, string $recommendation): array
    {
        return [
            'name' => $name,
            'status' => $ok ? 'ok' : 'warning',
            'value' => $value,
            'recommendation' => $ok ? '' : $recommendation,
        ];
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '-1') {
            return -1;
        }

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float)$value;

        return match ($unit) {
            'g' => (int)($number * 1024 * 1024 * 1024),
            'm' => (int)($number * 1024 * 1024),
            'k' => (int)($number * 1024),
            default => (int)$number,
        };
    }
}
