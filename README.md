# Official release feed

官方站专用的发布源插件。**不随核心发布包分发**，普通站点不需要安装；它们只需要在
`config/autoupdate.php` 里把 `feed` 指向官方地址：

```php
'feed' => 'https://nomenid.org/official-api/releases/latest',
```

## 安装（只官方站）

1. 把本目录放到站点 `plugins/official`；
2. 在 `config/plugins.php` 的 `enabled` 里加 `'official'`；
3. 复制 `config.example.json` 到站点 `config/plugins/official.json`，按需要改路由。
   这个文件是站点自己的，不要提交。

## 配置每个接口返回什么

`config/plugins/official.json`：

```json
{
    "base_path": "/official-api",
    "routes": {
        "releases/latest": "data/releases.json"
    }
}
```

- `base_path`：接口前缀，默认 `/official-api`。
- `routes`：左边是接口路径，右边是站点根下要原样返回的 JSON 文件。文件必须存在、在站点根内、
  以 `.json` 结尾。想加别的接口就再加一行。
- 没有配置文件或 `routes` 为空时插件不注册任何路由。

接口按 IP 限流 60 次 / 分钟，返回头带 `Cache-Control: public, max-age=60`。

## 发版流程

发版时只改 JSON 文件，不改插件：

```bash
php code-scripts/build-dist.php --priority=normal --min-supported=1.7.0
# 把 dist/release.json 放到官方站 data/releases.json
```

`release.json` 里应有 `version`、`priority`、`download`、`sha256`、`min_php`、
`min_supported`、`notes`、`released_at`；自动更新客户端读这些字段。
