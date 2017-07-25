<?php
/**
 * @file
 * Contains \Drupal\infinite_base\EventSubscriber\InfiniteEventSubscriber
 */

namespace Drupal\infinite_base\EventSubscriber;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Component\Utility\Html;

class InfiniteEventSubscriber implements EventSubscriberInterface {

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a FinishResponseSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('system.performance');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponse', 0);

    return $events;
  }

  /**
   * Filter a views AJAX response when the Load More pager is set.  Remove the
   * scrollTop commane and add in a viewsLoadMoreAppend AJAX command.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $request = $event->getRequest();

    // Add 'X-Robots-Tag' to Ajax requests.
    if ($response instanceof AjaxResponse) {
      $response->headers->set('X-Robots-Tag', 'noindex, follow');
    }

    // get requests can be cached
    if ($response instanceof AjaxResponse && $request->getMethod() === 'GET') {
      // set max_age to 300 as workaround for ajax caching problems
      $max_age = '300'; //$this->config->get('cache.page.max_age');
      $response->headers->set('cache-control', 'public, max-age=' . $max_age);
      Html::setIsAjax(TRUE);
    }
  }
}
