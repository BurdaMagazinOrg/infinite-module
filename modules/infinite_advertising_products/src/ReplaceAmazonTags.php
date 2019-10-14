<?php

namespace Drupal\infinite_advertising_products;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use QueryPath\DOMQuery;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Defines a Replace Amazon Tags object.
 *
 * @package Drupal\infinite_advertising_products
 */
class ReplaceAmazonTags {

  /**
   * Overridden Amazon Tag.
   *
   * @var string
   */
  protected $amazonTag;

  /**
   * The Symfony DOM crawler.
   *
   * @var Crawler
   */
  protected $crawler;

  /**
   * The QueryPath DOM query.
   *
   * @var DOMQuery $queryPath
   */
  protected $queryPath;

  /**
   * Array of product selectors for non-AMP.
   *
   * @var array
   */
  protected $selectors = [
    '.item-product[data-provider="amazon"]',
    '.item-product-slider[data-provider="amazon"]',
  ];

  /**
   * Array of product selectors for AMP.
   *
   * @var array
   */
  protected $selectorsAmp = [
    'a.item-product[data-vars-product-provider="amazon"]',
  ];

  protected $viewMode;

  /**
   * Constructs aReplaceAmazonTags object.
   *
   * @param $amazonTag
   *   The Amazon tag to override.
   * @param string $viewMode
   *   The selected view mode to act on.
   */
  public function __construct($amazonTag, $viewMode = 'full') {
    $this->amazonTag = $amazonTag;
    $this->crawler = new Crawler();
    $this->viewMode = $viewMode;
  }

  /**
   * Replace Amazon tag for all non-ProductDB products in given HTML markup.
   *
   * @param \Drupal\Core\Render\Markup $markup
   *   The HTML markup to replace Amazon tag of Amazon products.
   */
  public function replaceAmazonTags(Markup $markup) {

    // Loading the html here instead of passing it into the constructor
    // prevents problems with the charset.
    $this->crawler->addHtmlContent($markup);

    switch ($this->viewMode) {

      // Get all amazon products for AMP and replace Amazon tag.
      case 'amp':
        foreach ($this->selectorsAmp as $selectorAmp) {
          $itemProducts = $this->crawler->filter($selectorAmp);
          foreach ($itemProducts as $itemProduct) {
            $amazonUrl = $itemProduct->getAttribute('href');
            $itemProduct->setAttribute('href', $this->getChangedAmazonUrl($amazonUrl));
          }
        }
        break;

      // Get all amazon products for non-AMP and replace Amazon tag.
      default:
        foreach ($this->selectors as $selector) {
          $itemProducts = $this->crawler->filter($selector);
          foreach ($itemProducts as $itemProduct) {
            $amazonUrl = $itemProduct->getAttribute('data-external-url');
            $itemProduct->setAttribute('data-external-url', $this->getChangedAmazonUrl($amazonUrl));
          }
        }
    }
  }

  /**
   * Get Amazon URL with replaced Amazon tag.
   *
   * @param $amazonUrl
   *   The Amazon URL of a Amazon product.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The Amazon URL with replaced Amazon tag.
   */
  private function getChangedAmazonUrl($amazonUrl) {
    $amazonUrl = UrlHelper::parse($amazonUrl);
    $amazonUrl['query']['tag'] = $this->amazonTag;
    $options = [
      'query' => $amazonUrl['query'],
      'fragment' => $amazonUrl['fragment'],
    ];
    return Url::fromUri($amazonUrl['path'], $options)->toString();
  }

  /**
   * Get markup from given DOM crawler.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   */
  public function getMarkup() {
    libxml_use_internal_errors(TRUE);
    $this->queryPath = html5qp($this->crawler);
    libxml_clear_errors();

    $html = $this->queryPath->find('body')->innerHtml5();
    return Markup::create($html);
  }

}
