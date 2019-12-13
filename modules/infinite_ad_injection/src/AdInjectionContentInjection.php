<?php

namespace Drupal\infinite_ad_injection;

use DOMElement;
use Drupal\Core\Render\Markup;
use Error;
use QueryPath\DOMQuery;
use QueryPath\Exception;
use Symfony\Component\DomCrawler\Crawler;

class AdInjectionContentInjection
{
  /** @var DOMQuery */
  protected $queryPath;
  /** @var Crawler */
  protected $crawler;

  public function __construct()
  {
    $this->crawler = new Crawler();
  }

  public function injectAds(
    Markup $children,
    array $contents,
    array $selectors,
    int $first,
    int $each,
    string $placement
  )
  {
    // loading the html here instead of passing it into the constructor
    // prevents problems with the charset
    $this->crawler->addHtmlContent($children);
    $fragments = $this->crawler->filterXPath(implode('|', $selectors));

    // Rules: first ad is injected after the 2nd element, than after each 6th element
    /**
     * @var  int $fIndex
     * @var DOMElement $fragment
     */
    foreach ($fragments as $fIndex => $fragment) {
      if ($fIndex !== $first && $fIndex % $each !== $first) {
        continue;
      }

      // Special handling for text / viversum paragraphs.
      if (
        ($this->isTextParagraph($fragment) || $this->isViversumParagraph($fragment)) &&
        $pTags = $fragment->getElementsByTagName('p')
      ) {
        // Do not append ad injection if next paragraph is an advertising paragraph.
        if (
          $pTags->length <= ($first + 1) &&
          $nextNode = $fragments->getNode($fIndex + 1)
        ) {
          if ($this->nextNodeIsAdvertisingParagraph($nextNode)) {
            $fragments->getNode($fIndex); // Reset pointer of fragments.
            continue;
          }
        }

        // Add ad injection classes to first p-tag of text paragraph.
        try {
          $firstPTag = $fragment->getElementsByTagName('p')->item($first);
          $class = trim($firstPTag->getAttribute('class') . ' pread pread-' . $fIndex);
          $firstPTag->setAttribute('class', $class);
        } catch (Error $e) {
        }
      } else {
        // Do not append ad injection if next paragraph is an advertising paragraph.
        if ($nextNode = $fragments->getNode($fIndex + 1)) {
          if ($this->nextNodeIsAdvertisingParagraph($nextNode)) {
            $fragments->getNode($fIndex); // Reset pointer of fragments.
            continue;
          }
        }

        $class = trim($fragment->getAttribute('class') . ' pread pread-' . $fIndex);
        $fragment->setAttribute('class', $class);
      }
    }

    $this->replaceContent($contents, $placement);
  }

  public function getMarkup()
  {
    $html = $this->queryPath->find('body')->innerHtml5();
    return Markup::create($html);
  }

  /**
   * @param array $contents
   * @param string $placement before|after
   * @throws Exception
   */
  protected function replaceContent(array $contents, string $placement)
  {
    $contentsCount = count($contents);
    libxml_use_internal_errors(TRUE);
    $this->queryPath = html5qp($this->crawler);
    $this->queryPath->find('.pread')
      ->each(function ($i, $e) use ($contents, $contentsCount, $placement) {
        if ($content = $contents[$i % $contentsCount]) {
          // Wrapping is required because querypath creates a whole valid html structure
          // qp('<div>some content</div>') -> <html><head></head><body><div>some content</div></body></html>
          // qp('<script src="..."></script>') -> <html><head><script src="..."></script></head></html>
          $contentWrapped = '<div class="ad-injct-wrpr">' . $content . '</div>';
          $contentInner = qp($contentWrapped)->find('body div.ad-injct-wrpr')->innerHTML();
          if ($i === 0 && qp($contentWrapped)->find('amp-fx-flying-carpet')->count()) {
            $placement = 'before';
            qp($e)->addClass('p--text-first');
          }
          qp($e)->removeClass('pread')->$placement($contentInner);
        }
      });
    libxml_clear_errors();
  }

  /**
   * @param DOMElement $nextNode
   * @return bool
   */
  protected function nextNodeIsAdvertisingParagraph(DOMElement $nextNode): bool
  {
    $nextNodeClasses = $nextNode->getAttribute('class');
    return strpos($nextNodeClasses, 'item-paragraph--advertising-products-paragraph') !== FALSE ||
      strpos($nextNodeClasses, 'p--advertising-products-paragraph') !== FALSE;
  }

  /**
   * @param $fragment
   * @return bool
   */
  protected function isTextParagraph(DOMElement $fragment): bool
  {
    $fragmentClasses = $fragment->getAttribute('class');
    return strpos($fragmentClasses, 'item-paragraph--text') !== FALSE ||
      strpos($fragmentClasses, 'p--text') !== FALSE;
  }

  /**
   * @param DOMElement $fragment
   * @return bool
   */
  protected function isViversumParagraph(DOMElement $fragment): bool
  {
    $fragmentClasses = $fragment->getAttribute('class');
    return strpos($fragmentClasses, 'item-paragraph--viversum-zodiac') !== FALSE ||
      strpos($fragmentClasses, 'p--viversum-zodiac') !== FALSE;
  }
}
