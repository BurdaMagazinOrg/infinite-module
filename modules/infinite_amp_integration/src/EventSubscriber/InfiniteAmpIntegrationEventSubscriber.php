<?php

namespace Drupal\infinite_amp_integration\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class InfiniteAmpIntegrationEventSubscriber implements EventSubscriberInterface {

  public function checkForAmpPage(GetResponseEvent $event) {
    /** @var \Drupal\amp\Routing\AmpContext $amp_context */
    $amp_context = \Drupal::service('router.amp_context');
    if (isset($amp_context) && $amp_context->isAmpRoute()) {
      if (function_exists('newrelic_disable_autorum')) {
        newrelic_disable_autorum();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForAmpPage');
    return $events;
  }
}