# Official release feed

官方站专用的发布源插件。注册 `GET /official-api/releases/latest`，读取站点根
`data/releases.json`，返回最新发布信息。接口按 IP 限流 60 次 / 分钟。

**普通站点不要装。** 它们只需要在 `config/autoupdate.php` 里把 `feed` 指向官方地址：

```php
'feed' => 'https://nomenid.org/official-api/releases/latest',
```

插件不随核心发布包分发。安装：

```bash
# 把本目录放到站点 plugins/official，然后在 config/plugins.php 的 enabled 里加 'official'
```

发布文件格式见官方文档 `docs/ops/auto-update.md`；`php code-scripts/build-dist.php
--priority=normal --min-supported=1.7.0` 生成的 `dist/release.json` 放到官方站 `data/releases.json`。
