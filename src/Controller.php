<?php
/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-official
 * Project: https://nomenid.org/
 */

namespace Nomen\Plugin\Official;

use Nomen\Src\{RateLimiter, Response};

class Controller
{
    /** 把一个配置好的 JSON 文件原样作为接口返回 */
    public function serve(string $file, string $route): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rateCheck = RateLimiter::check('official:' . $route . ':' . $ip, 60, 60);
        if (!$rateCheck['allowed']) {
            header('Retry-After: ' . $rateCheck['retry_after']);
            Response::json(['error' => 'rate_limited'], 429);
            return;
        }

        $root = dirname(__DIR__, 3);
        $rel = trim(str_replace('\\', '/', $file), '/');
        if ($rel === '' || !str_ends_with(strtolower($rel), '.json')
            || preg_match('#(^|/)\.\.?(/|$)#', $rel) === 1
            || str_starts_with($rel, '/')) {
            Response::json([
                'error' => 'invalid_endpoint_config',
                'error_description' => 'The configured file path is not a safe JSON path.',
            ], 500);
            return;
        }

        $path = $root . '/' . $rel;
        $realRoot = (string)realpath($root);
        $realPath = (string)realpath($path);
        if ($realRoot === '' || $realPath === ''
            || !str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)) {
            Response::json([
                'error' => 'endpoint_unavailable',
                'error_description' => 'The configured JSON file is missing.',
            ], 404);
            return;
        }

        $data = json_decode((string)@file_get_contents($path), true);
        if (!is_array($data)) {
            Response::json([
                'error' => 'invalid_endpoint_payload',
                'error_description' => 'The configured JSON file is malformed.',
            ], 500);
            return;
        }

        header('Cache-Control: public, max-age=60');
        Response::json($data);
    }
}
