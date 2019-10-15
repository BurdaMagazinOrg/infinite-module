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
   * @var DOMQuery
   */
  protected $queryPath;

  /**
   * The attribute we want to replace.
   *
   * @var string
   */
  protected $replaceAttr;

  /**
   * Array of product selectors for non-AMP.
   *
   * @var array
   */
  protected $selectors = [
    'a.item-product[data-vars-product-provider="amazon"]',
    '.item-ecommerce[data-provider="amazon"]',
  ];

  /**
   * Constructs a ReplaceAmazonTags object.
   *
   * @param $amazonTag
   *   The Amazon tag to override.
   * @param string $replaceAttr
   *   The attribute we want to replace.
   */
  public function __construct($amazonTag, $replaceAttr = 'data-external-url') {
    $this->amazonTag = $amazonTag;
    $this->crawler = new Crawler();
    $this->replaceAttr = $replaceAttr;
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

    foreach ($this->selectors as $selector) {
      $itemProducts = $this->crawler->filter($selector);
      foreach ($itemProducts as $itemProduct) {
        $amazonUrl = $itemProduct->getAttribute($this->replaceAttr);
        if (parse_url($amazonUrl) !== FALSE) {
          $itemProduct->setAttribute($this->replaceAttr, $this->getChangedAmazonUrl($amazonUrl));
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
