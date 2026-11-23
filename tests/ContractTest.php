<?php
// ==========================================================
// Tests :: Schema Contracts
// ==========================================================
//
// Exercises the plugin's public JSON-LD behavior with fixtures.
//
// ==========================================================

declare(strict_types=1);

namespace Zeimoto\Schema\Tests;

use Kirby\Cms\Page;
use Zeimoto\Schema\Schema;

final class ContractTest extends PluginTestCase
{
  public function testNonIndexablePagesEmitNoSchema(): void
  {
    $this->app(site: ["content" => ["indexingStatus" => "false"]]);
    $page = new Page(["slug" => "ordinary", "content" => ["indexingStatus" => "true"]]);

    self::assertSame("", (new Schema($page, compute($page)))->render());
  }

  public function testFaqsAreEmittedForAnyIndexableTemplate(): void
  {
    $app = $this->app(site: ["content" => ["indexingStatus" => "true"]]);
    $page = new Page([
      "slug" => "contact",
      "template" => "contact",
      "content" => [
        "title" => "Contact",
        "indexingStatus" => "true",
        "faqs" => [["question" => "Question?", "answer" => "Answer."]],
      ],
    ]);

    $html = (new Schema($page, compute($page)))->render();

    self::assertStringContainsString('"@type": "FAQPage"', $html);
    self::assertStringContainsString('"name": "Question?"', $html);
    self::assertStringContainsString('"text": "Answer."', $html);
  }

  public function testArticleGraphIncludesSafeJsonLdAndSharedNodes(): void
  {
    $this->app(site: ["content" => ["title" => "Site", "indexingStatus" => "true"]]);
    $page = new Page([
      "slug" => "story",
      "content" => ["title" => "A </script> story", "schemaType" => "Article", "indexingStatus" => "true"],
    ]);

    $html = (new Schema($page, compute($page)))->render();

    self::assertStringContainsString('"@type": "Article"', $html);
    self::assertStringContainsString('"@type": "WebSite"', $html);
    self::assertStringContainsString('"@type": "BreadcrumbList"', $html);
    self::assertStringNotContainsString("</script> story", $html);
    self::assertStringContainsString("\\u003C/script\\u003E", $html);
  }
}
