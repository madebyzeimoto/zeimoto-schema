# zeimoto Schema

`zeimoto/schema` renders indexable pages as Schema.org JSON-LD. It owns the
supported page graphs, shared website/entity/breadcrumb nodes, FAQ graphs, and
the reusable Schema Type blueprint.

## Installation

Requires PHP 8.5, Kirby 6, and `zeimoto/compute`:

```sh
composer require zeimoto/schema
```

The plugin is harmless when pages are not indexable. It reads the host's
`faqs` structure when present and emits a separate `FAQPage` graph for any
indexable template with populated FAQs.

## Usage

Call the registered snippet from the document head:

```php
<?php snippet("zeimoto/schema"); ?>
```

The host may consume the blueprint with `extends: fields/schema/type`.

## Development

Open `kirby.code-workspace` while the plugin is installed under the kit's
`site/plugins/` directory. From the host root, run:

```sh
node tools/check-style.mjs --plugin site/plugins/zeimoto-schema
php vendor/bin/phpunit -c site/plugins/zeimoto-schema/phpunit.xml
```
