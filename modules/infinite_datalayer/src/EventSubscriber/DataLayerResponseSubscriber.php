<?php

namespace Drupal\infinite_datalayer\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber for adding attachments to responses.
 */
class DataLayerResponseSubscriber implements EventSubscriberInterface {

  /**
   * The data layer store service.
   *
   * @var KeyValueStoreInterface
   */
  protected $dataLayerStore;

  /**
   * Constructs a DataLayerResponseSubscriber object.
   *
   * @param KeyValueStoreInterface $datalayer_store
   *   The data layer store service.
   */
  public function __construct(KeyValueStoreInterface $datalayer_store) {
    $this->dataLayerStore = $datalayer_store;
  }

  /**
   * Adds attachments that can be also be processed on AJAX requests.
   *
   * @param FilterResponseEvent $event
   *   The response event.
   */
  public function onResponse(FilterResponseEvent $event) {
    if ($event->getResponse() instanceof AjaxResponse && !empty($this->dataLayerStore->getAll())) {
      $attachments = [
        'library' => ['core/drupalSettings'],
        'drupalSettings' => ['datalayer' => $this->dataLayerStore->getAll()],
      ];
      $event->getResponse()->addAttachments($attachments);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Prioriy must be higher than AjaxResponseSubscriber's (-100).
    // 0 is the default, but in this case explicit is better than implicit.
    return [
      KernelEvents::RESPONSE => [['onResponse', 0]],
    ];
  }
}
