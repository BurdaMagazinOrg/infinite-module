<?php
/**
 *
 */

namespace Drupal\infinite_views\Plugin\xsl_processor;


use Drupal\xsl_process\XslProcessorBase;

/**
 * Provides a processor for the Focus Syndication feed.
 *
 * @XslProcessor(
 *   id = "focus_syndication",
 *   stylesheet_uri = "focus_syndication.xsl",
 *   name = @Translation("Focus Syndication"),
 *   php_functions_provider = "\Drupal\xsl_process\DefaultPhpFunctionsProvider"
 * )
 */
class FocusSyndication extends XslProcessorBase {

}
