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
    public function latest(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rateCheck = RateLimiter::check("official:releases:{$ip}", 60, 60);
        if (!$rateCheck['allowed']) {
            header('Retry-After: ' . $rateCheck['retry_after']);
            Response::json(['error' => 'rate_limited'], 429);
            return;
        }

        $file = dirname(__DIR__, 3) . '/data/releases.json';
        if (!is_file($file)) {
            Response::json([
                'error' => 'release_feed_unavailable',
                'error_description' => 'This site does not publish a release feed.',
            ], 404);
            return;
        }

        $data = json_decode((string)@file_get_contents($file), true);
        $release = is_array($data) ? self::pickLatest($data) : null;
        $payload = $release !== null ? self::payload($release) : null;
        if ($payload === null) {
            Response::json([
                'error' => 'invalid_release_feed',
                'error_description' => 'The release feed is malformed.',
            ], 500);
            return;
        }

        header('Cache-Control: public, max-age=60');
        Response::json(['product' => 'Nomen'] + $payload);
    }

    /** @return array<string, mixed>|null */
    private static function pickLatest(array $data): ?array
    {
        if (!isset($data['releases']) || !is_array($data['releases'])) {
            return $data;
        }

        $best = null;
        $bestVersion = '';
        foreach ($data['releases'] as $release) {
            if (!is_array($release)) {
                continue;
            }
            $version = trim((string)($release['version'] ?? ''));
            if (preg_match('/^\d+(\.\d+){1,3}$/', $version) !== 1) {
                continue;
            }
            if ($best === null || version_compare($version, $bestVersion, '>')) {
                $best = $release;
                $bestVersion = $version;
            }
        }

        return $best;
    }

    /** @return array<string, mixed>|null */
    private static function payload(array $release): ?array
    {
        $version = trim((string)($release['version'] ?? ''));
        if (preg_match('/^\d+(\.\d+){1,3}$/', $version) !== 1) {
            return null;
        }

        $download = trim((string)($release['download'] ?? ''));
        if (preg_match('#^https?://#i', $download) !== 1) {
            return null;
        }

        $priority = strtolower(trim((string)($release['priority'] ?? 'normal')));
        if (!in_array($priority, ['security', 'critical', 'normal', 'feature'], true)) {
            $priority = 'normal';
        }

        $out = [
            'version'  => $version,
            'priority' => $priority,
            'download' => $download,
        ];

        $sha = strtolower(trim((string)($release['sha256'] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/', $sha) === 1) {
            $out['sha256'] = $sha;
        }

        $minPhp = trim((string)($release['min_php'] ?? ''));
        if (preg_match('/^\d+(\.\d+){1,2}$/', $minPhp) === 1) {
            $out['min_php'] = $minPhp;
        }

        $minSupported = trim((string)($release['min_supported'] ?? ''));
        if (preg_match('/^\d+(\.\d+){1,3}$/', $minSupported) === 1) {
            $out['min_supported'] = $minSupported;
        }

        $notes = trim((string)($release['notes'] ?? ''));
        if (preg_match('#^https?://#i', $notes) === 1) {
            $out['notes'] = $notes;
        }

        $released = trim((string)($release['released_at'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}([T ][0-9:.]+Z?)?$/', $released) === 1) {
            $out['released_at'] = mb_substr($released, 0, 40);
        }

        return $out;
    }
}
