<?php
// ==========================================================
// Plugins :: zeimoto :: Schema
// ==========================================================
//
// Owns the indexable page JSON-LD renderer and Schema.org
// type blueprint used by the host's visibility tabs.
//
// ==========================================================

declare(strict_types=1);

use Zeimoto\Schema\Schema;

$flatten = require __DIR__ . "/languages/flatten-blueprint-translations.php";

load([
  Schema::class => "src/Schema.php",
], __DIR__);

Kirby::plugin("zeimoto/schema", [
  "translations" => [
    "en" => $flatten(require __DIR__ . "/languages/blueprints/en.php"),
  ],
  "blueprints" => [
    "fields/schema/type" => __DIR__ . "/blueprints/fields/schema/type.yml",
  ],
  "snippets" => [
    "zeimoto/schema" => __DIR__ . "/snippets/schema.php",
  ],
]);
