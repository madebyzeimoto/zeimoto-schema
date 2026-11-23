<?php
// ==========================================================
// Snippets :: zeimoto :: Schema
// ==========================================================
//
// Emits the plugin's indexable page JSON-LD graphs.
//
// ==========================================================

declare(strict_types=1);

use Zeimoto\Schema\Schema;

echo (new Schema($page, $compute))->render();
