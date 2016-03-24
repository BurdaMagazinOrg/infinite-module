<?php
/**
 * @file
 * Contains \Drupal\infinite_base\Plugin\Filter\FilterExternalLinks.
 */

namespace Drupal\infinite_base\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to add rel="nofollow" to external links.
 *
 * @Filter(
 *   id = "filter_external_links",
 *   title = @Translation("Add rel='nofollow' to external links"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "filter_external_links_nofollow" = TRUE,
 *     "filter_external_links_target" = TRUE,
 *   },
 *   weight = 10
 * )
 */
class FilterExternalLinks extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['filter_external_links_nofollow'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add rel="nofollow" to external links'),
      '#default_value' => $this->settings['filter_external_links_nofollow'],
    );
    $form['filter_external_links_target'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add target="_blank" attribute to external links'),
      '#default_value' => $this->settings['filter_external_links_target'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->filterLinkAttributes($text));
  }

  /**
   * Adds attributes rel="nofollow" and target="_blank" to external links.
   *
   * @param string $text
   *
   * @return string
   */
  public function filterLinkAttributes($text) {
    $html_dom = Html::load($text);
    $links = $html_dom->getElementsByTagName('a');

    foreach ($links as $link) {
      if (!$this->isExternalUrl($link->getAttribute('href'))) {
        continue;
      }
      if ($this->settings['filter_external_links_nofollow']) {
        $link->setAttribute('rel', 'nofollow');
      }
      if ($this->settings['filter_external_links_target']) {
        $link->setAttribute('target', '_blank');
      }
    }

    return trim(Html::serialize($html_dom));
  }

  /**
   * Returns TRUE if the given URI is considered internal, FALSE otherwise.
   *
   * @param type $uri
   *
   * @return boolean
   */
  public function isExternalUrl($uri) {
    $internal_domains = \Drupal::config('infinite_base.filter_external_links')->get('internal_domains') ?: [];
    $uri_domain = parse_url($uri, PHP_URL_HOST);

    try {
      $url = Url::fromUri($uri);
    }
    catch (\InvalidArgumentException $e) {
      return FALSE;
    }
    return $url->isExternal() && !in_array($uri_domain, $internal_domains);
  }

}
