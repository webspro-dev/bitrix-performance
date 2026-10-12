<?php

namespace Stadigit\Performance;

use Bitrix\Main\Data\Cache;

final class CacheManager
{
    public static function clearCache(): bool
    {
        if (!class_exists(Cache::class)) {
            throw new \RuntimeException('Bitrix cache API is unavailable.');
        }

        $cache = Cache::createInstance();
        $cache->cleanDir();

        return true;
    }

    public static function clearManagedCache(): bool
    {
        if (!class_exists('\\CManagedCache')) {
            throw new \RuntimeException('Managed cache API is unavailable.');
        }

        global $CACHE_MANAGER;

        if (!is_object($CACHE_MANAGER)) {
            $CACHE_MANAGER = new \CManagedCache();
        }

        $CACHE_MANAGER->CleanAll();

        return true;
    }
}
