<?php

namespace Drupal\Tests\infinite_base\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test infinite_base admin interface.
 *
 * @group infinite_base
 */
class FilterExternalLinksFormTest extends BrowserTestBase {

  static $modules = ['infinite_base'];

  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer filters']));
  }

  public function testForm() {
    $this->drupalGet(Url::fromRoute('infinite_base.filter_external_links'));
    $page = $this->getSession()->getPage();

    $domains = [
      'example.com',
      '',
      ' ',
      'foo.com ',
      ' bar.de'
    ];
    $page->fillField('internal_domains', implode("\r\n", $domains));
    $page->pressButton('Save configuration');

    $domains = [
      'example.com',
      'foo.com',
      'bar.de',
    ];
    $this->assertSame($domains, \Drupal::config('infinite_base.filter_external_links')->get('internal_domains'));
  }

}
