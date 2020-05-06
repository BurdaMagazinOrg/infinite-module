<?php

namespace Drupal\infinite_views\Plugin\xsl_processor;

use Drupal\xsl_process\XslProcessorBase;

/**
 * Provides a processor for the MSN feed.
 *
 * @XslProcessor(
 *   id = "msn",
 *   stylesheet_uri = "msn.xsl",
 *   name = @Translation("MSN Feed"),
 *   php_functions_provider = "\Drupal\xsl_process\DefaultPhpFunctionsProvider"
 * )
 */
class Msn extends XslProcessorBase {

}
