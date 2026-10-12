<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('stadigit.performance', [
    'Stadigit\\Performance\\Diagnostics' => 'lib/diagnostics.php',
    'Stadigit\\Performance\\CacheManager' => 'lib/cachemanager.php',
]);
