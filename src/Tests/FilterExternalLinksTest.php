<?php

/**
 * @file
 * Contains \Drupal\infinite_base\Tests\FilterExternalLinksTest.
 */

namespace Drupal\infinite_base\Tests;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterPluginCollection;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests FilterExternalLinks filter.
 *
 * @group filter
 */
class FilterExternalLinksTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'filter', 'infinite_base');

  /**
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected $filter;

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system', 'infinite_base'));

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, array());
    $this->filter = $bag->get('filter_external_links');
  }

  public function testLinkAttributesAreAdded() {
    $url = 'http://example.com/a';
    $text = "<p>An <a href=\"$url\">external</a> link.</p>";
    $html_dom = Html::load($this->filter->process($text, 'und')->getProcessedText());
    $links = $html_dom->getElementsByTagName('a');
    $this->assertEqual(1, count($links));
    $this->assertTrue($links[0]->hasAttribute('href'));
    $this->assertTrue($links[0]->hasAttribute('rel'));
    $this->assertTrue($links[0]->hasAttribute('target'));
    $this->assertEqual($url, $links[0]->getAttribute('href'));
    $this->assertEqual('nofollow', $links[0]->getAttribute('rel'));
    $this->assertEqual('_blank', $links[0]->getAttribute('target'));
  }

  public function testInternalLinksAreIgnored() {
    $url = 'nodes';
    $text_1 = "<p>An <a href=\"$url\">internal</a> link.</p>";
    $this->assertIdentical($text_1, $this->filter->process($text_1, 'und')->getProcessedText());
    $other_url = 'http://instyle.de/a/b';
    $text_2 = "<p>An <a href=\"$other_url\">internal</a> link with absolute_url.</p>";
    $this->assertIdentical($text_2, $this->filter->process($text_2, 'und')->getProcessedText());
  }

}
