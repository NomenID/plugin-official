<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-official
 * Project: https://nomenid.org/
 */

namespace Nomen\Plugin\Official;

use Nomen\Src\Plugins;

class Plugin
{
    public static function boot(): void
    {
        // 只有官方站需要这个接口；一般站点不装这个插件，只拉官方地址。
        Plugins::route('GET', '/official-api/releases/latest', [Controller::class, 'latest']);
    }
}
