<?php

namespace Drupal\infinite_views\Plugin\xsl_processor;

use Drupal\xsl_process\XslProcessorBase;

/**
 * Provides a processor for the RSS feed.
 *
 * @XslProcessor(
 *   id = "rss",
 *   stylesheet_uri = "rss.xsl",
 *   name = @Translation("RSS Feed"),
 *   php_functions_provider = "\Drupal\xsl_process\DefaultPhpFunctionsProvider"
 * )
 */
class Rss extends XslProcessorBase {

}
