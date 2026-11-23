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

load([
  Schema::class => "src/Schema.php",
], __DIR__);

Kirby::plugin("zeimoto/schema", [
  "blueprints" => [
    "fields/schema/type" => __DIR__ . "/blueprints/fields/schema/type.yml",
  ],
  "snippets" => [
    "zeimoto/schema" => __DIR__ . "/snippets/schema.php",
  ],
]);
