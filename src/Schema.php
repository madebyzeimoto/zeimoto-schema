<?php
// ==========================================================
// Plugins :: zeimoto :: Schema :: Schema
// ==========================================================
//
// Builds indexable page JSON-LD graphs from Compute values.
//
// ==========================================================

declare(strict_types=1);

namespace Zeimoto\Schema;

use Kirby\Cms\Page;
use Zeimoto\Compute\Compute;

final class Schema
{
  private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;

  public function __construct(
    private readonly Page $page,
    private readonly Compute $compute,
  ) {
  }

  public function render(): string
  {
    if (!$this->page->isIndexable()) {
      return "";
    }

    $output = $this->script($this->primaryGraph());
    if ($faqGraph = $this->faqGraph()) {
      $output .= $this->script($faqGraph);
    }

    return $output;
  }

  private function primaryGraph(): array
  {
    $graph = match ($this->compute->schemaType()) {
      "HomePage" => $this->homePageGraph(),
      "AboutPage", "ProfilePage" => $this->entityPageGraph(),
      "CollectionPage" => $this->collectionPageGraph(),
      "Article" => $this->articleGraph(),
      "CreativeWork" => $this->creativeWorkGraph(),
      default => $this->defaultGraph(),
    };

    return ["@context" => "https://schema.org", "@graph" => $graph];
  }

  private function defaultGraph(): array
  {
    $canonical = $this->compute->canonicalUrl();
    return [$this->webPageNode($this->compute->schemaType(), $canonical), $this->websiteNode(), $this->breadcrumbsNode()];
  }

  private function homePageGraph(): array
  {
    $canonical = $this->compute->canonicalUrl();
    $webPage = $this->webPageNode("WebPage", $canonical);
    $webPage["about"] = ["@id" => $this->compute->entityId()];

    return [$webPage, $this->entityNode(), $this->websiteNode(), $this->breadcrumbsNode()];
  }

  private function entityPageGraph(): array
  {
    $canonical = $this->compute->canonicalUrl();
    $entityType = $this->compute->entityType();
    $entityId = $this->compute->entityId();
    $isOrgOrBrand = $entityType === "organization" || $entityType === "brand";
    $webPage = $this->webPageNode($isOrgOrBrand ? $this->compute->schemaType() : "ProfilePage", $canonical);
    $webPage[$isOrgOrBrand ? "about" : "mainEntity"] = ["@id" => $entityId];

    return [$webPage, $this->entityNode(), $this->websiteNode(), $this->breadcrumbsNode()];
  }

  private function collectionPageGraph(): array
  {
    $canonical = $this->compute->canonicalUrl();
    $listed = $this->page->children()->listed();
    $items = [];
    foreach ($listed->limit(10)->values() as $index => $child) {
      $childCompute = compute($child);
      $items[] = [
        "@type" => "ListItem",
        "position" => $index + 1,
        "item" => [
          "@id" => $childCompute->canonicalUrl(),
          "url" => $childCompute->canonicalUrl(),
          "name" => $childCompute->title()->value(),
        ],
      ];
    }

    $webPage = $this->webPageNode("CollectionPage", $canonical);
    $webPage["mainEntity"] = [
      "@type" => "ItemList",
      "numberOfItems" => $listed->count(),
      "itemListElement" => $items,
    ];

    return [$webPage, $this->websiteNode(), $this->breadcrumbsNode()];
  }

  private function articleGraph(): array
  {
    return $this->creativeGraph("Article", "publisher");
  }

  private function creativeWorkGraph(): array
  {
    return $this->creativeGraph("CreativeWork", "copyrightHolder");
  }

  private function creativeGraph(string $type, string $agentProperty): array
  {
    $canonical = $this->compute->canonicalUrl();
    $agentId = $this->compute->schemaAgentId();
    $work = [
      "@type" => $type,
      "@id" => $canonical . "#" . strtolower($type),
      "url" => $canonical,
      "headline" => $this->compute->title()->value(),
      "inLanguage" => $this->compute->lang(),
      "dateModified" => $this->page->modified("c"),
      "isPartOf" => ["@id" => $canonical . "#webpage"],
      "mainEntityOfPage" => ["@id" => $canonical . "#webpage"],
      "author" => ["@id" => $agentId],
      $agentProperty => ["@id" => $agentId],
    ];
    $this->addDescription($work);
    $this->addImage($work);

    return [$work, $this->webPageNode("WebPage", $canonical), $this->websiteNode(), $this->entityNode($this->compute->schemaAgentType(), $agentId), $this->breadcrumbsNode()];
  }

  private function webPageNode(string $type, string $canonical): array
  {
    $node = [
      "@type" => $type,
      "@id" => $canonical . "#webpage",
      "url" => $canonical,
      "name" => $this->compute->title()->value(),
      "inLanguage" => $this->compute->lang(),
      "isPartOf" => ["@id" => $this->page->site()->url() . "#website"],
      "breadcrumb" => ["@id" => $canonical . "#breadcrumb"],
    ];
    $this->addDescription($node);

    return $node;
  }

  private function websiteNode(): array
  {
    $site = $this->page->site();
    $siteCompute = compute($site);
    $node = [
      "@type" => "WebSite",
      "@id" => $site->url() . "#website",
      "url" => $site->url(),
      "name" => $siteCompute->entityName()->value(),
      "inLanguage" => $siteCompute->lang(),
    ];
    if ($siteCompute->description()->isNotEmpty()) {
      $node["description"] = $siteCompute->description()->value();
    }
    if ($sameAs = $siteCompute->sameAs()) {
      $node["sameAs"] = $sameAs;
    }

    return $node;
  }

  private function entityNode(string|null $nodeType = null, string|null $nodeId = null): array
  {
    $site = $this->page->site();
    $siteCompute = compute($site);
    $entityType = $this->compute->entityType();
    $node = [
      "@type" => $nodeType ?? $this->compute->schemaEntityType(),
      "@id" => $nodeId ?? $this->compute->entityId(),
      "name" => $this->compute->entityName()->value(),
      "url" => $this->compute->entityUrl(),
    ];
    if ($siteCompute->description()->isNotEmpty()) {
      $node["description"] = $siteCompute->description()->value();
    }
    if ($entityType === "person" && $site->entityJobTitle()->isNotEmpty()) {
      $node["jobTitle"] = $site->entityJobTitle()->value();
    }
    if ($entityType === "person" && $site->entityTopics()->isNotEmpty()) {
      $node["knowsAbout"] = $site->entityTopics()->split();
    }
    if ($avatar = $site->avatarImage()->toFile()) {
      $node[$entityType === "person" ? "image" : "logo"] = [
        "@type" => "ImageObject",
        "url" => $avatar->url(),
        "width" => $avatar->width(),
        "height" => $avatar->height(),
      ];
    }
    if ($sameAs = $this->compute->sameAs()) {
      $node["sameAs"] = $sameAs;
    }

    return $node;
  }

  private function breadcrumbsNode(): array
  {
    $trail = [$this->page->site()->homePage(), ...$this->page->parents()->flip()->values(), $this->page];
    $seen = [];
    $items = [];
    foreach ($trail as $crumb) {
      if (!$crumb || in_array($crumb->id(), $seen, true)) {
        continue;
      }
      $seen[] = $crumb->id();
      $crumbCompute = compute($crumb);
      $items[] = [
        "@type" => "ListItem",
        "position" => count($items) + 1,
        "item" => [
          "@id" => $crumbCompute->canonicalUrl(),
          "name" => $crumb->isHomePage() ? $this->compute->entityName()->value() : $crumbCompute->title()->value(),
        ],
      ];
    }

    return [
      "@type" => "BreadcrumbList",
      "@id" => $this->compute->canonicalUrl() . "#breadcrumb",
      "itemListElement" => $items,
    ];
  }

  private function faqGraph(): array|null
  {
    $faqs = $this->page->faqs()->toStructure();
    if ($faqs->isEmpty()) {
      return null;
    }

    $questions = [];
    foreach ($faqs as $faq) {
      $answer = $this->compute->plainText($faq->answer()->kirbytext());
      $questions[] = [
        "@type" => "Question",
        "name" => $faq->question()->value(),
        "acceptedAnswer" => ["@type" => "Answer", "text" => $answer],
      ];
    }

    $canonical = $this->compute->canonicalUrl();
    $faqPage = $this->webPageNode("FAQPage", $canonical);
    $faqPage["@id"] = $canonical . "#faq";
    $faqPage["mainEntity"] = $questions;

    return ["@context" => "https://schema.org", "@graph" => [$faqPage]];
  }

  private function addDescription(array &$node): void
  {
    if ($this->compute->description()->isNotEmpty()) {
      $node["description"] = $this->compute->description()->value();
    }
  }

  private function addImage(array &$node): void
  {
    if ($image = $this->compute->opengraphImage()) {
      $node["image"] = [
        "@type" => "ImageObject",
        "url" => $image->url,
        "width" => $image->width,
        "height" => $image->height,
      ];
    }
  }

  private function script(array $graph): string
  {
    return "<script type=\"application/ld+json\">" . json_encode($graph, self::JSON_FLAGS) . "</script>\n";
  }
}
