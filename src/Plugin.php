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

/**
 * 官方站专用的发布源插件。
 *
 * 路由和返回内容都在站点自己的 config/plugins/official.json 里手写：
 *
 * {
 *   "base_path": "/official-api",
 *   "routes": {
 *     "releases/latest": "data/releases.json"
 *   }
 * }
 *
 * 发版流程只改 data/releases.json（或你配置的其它 JSON 文件），插件本身不用动。
 */
class Plugin
{
    public static function boot(): void
    {
        $config = self::config();
        if ($config === null) {
            return;
        }

        [$base, $routes] = $config;
        foreach ($routes as $route => $file) {
            Plugins::route('GET', $base . '/' . $route, static function () use ($file, $route): void {
                (new Controller())->serve($file, $route);
            });
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}|null
     */
    private static function config(): ?array
    {
        $config = Plugins::config('official', []);
        if (!is_array($config)) {
            return null;
        }

        $base = trim((string)($config['base_path'] ?? '/official-api'));
        if ($base === '' || !preg_match('#^/[a-z0-9._/-]+$#i', $base)) {
            error_log('[official] base_path 不合法，插件不注册路由');
            return null;
        }
        $base = '/' . trim($base, '/');

        $routes = [];
        foreach ((array)($config['routes'] ?? []) as $route => $file) {
            $route = trim(str_replace('\\', '/', (string)$route), '/');
            $file = trim((string)$file);
            if ($route === '' || preg_match('#(^|/)\.\.?(/|$)#', $route) === 1
                || preg_match('/^[a-z0-9._\/-]+$/i', $route) !== 1
                || $file === '') {
                error_log('[official] 忽略不合法的接口配置: ' . $route);
                continue;
            }
            $routes[$route] = $file;
        }

        return $routes === [] ? null : [$base, $routes];
    }
}
