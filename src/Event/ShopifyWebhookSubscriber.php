<?php
/**
 * @file
 * Contains webhook subscriber functionality.
 */
namespace Drupal\shopify\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ShopifyWebhookSubscriber
 *
 * Provides the webhook subscriber functionality.
 */
class ShopifyWebhookSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['shopify.webhook'][] = ['onIncomingWebhook'];
    return $events;
  }

  /**
   * Process an incoming webhook.
   *
   * @param \Drupal\shopify\Event\ShopifyWebhookEvent $event
   *   Logs an incoming webhook of the setting is on.
   */
  public function onIncomingWebhook(ShopifyWebhookEvent $event) {
    $config = \Drupal::config('shopify.webhooks');
    if ($config->get('log_webhooks')) {
      // Log this incoming webhook data.
      \Drupal::logger('shopify.webhook')->info(t('<strong>Topic:</strong> @topic<br />
      <strong>Data:</strong> @data.', [
        '@topic' => $event->topic,
        '@data' => var_export($event->data, TRUE),
      ]));
    }
    $method = 'webhook_' . str_replace('/', '_', $event->topic);
    if (method_exists($this, $method)) {
      $this->{$method}($event->data);
    }
  }

  /**
   * Handle updating of products.
   *
   * @param \stdClass $data
   */
  private function webhook_products_update(\stdClass $data) {
    // @todo: Needs functionality.
  }

  /**
   * Handle creating of products.
   *
   * @param \stdClass $data
   */
  private function webhook_products_create(\stdClass $data) {
    // @todo: Needs functionality.
  }

  /**
   * Handle deleting of products.
   *
   * @param \stdClass $data
   */
  private function webhook_products_delete(\stdClass $data) {
    // @todo: Needs functionality.
  }

  /**
   * Handle creating of collections.
   *
   * @param \stdClass $data
   */
  private function webhook_collections_create(\stdClass $data) {
    // @todo: Needs functionality.
  }

  /**
   * Handle updating of collections.
   *
   * @param \stdClass $data
   */
  private function webhook_collections_update(\stdClass $data) {
    // @todo: Needs functionality.
  }

  /**
   * Handle deleting of collections.
   *
   * @param \stdClass $data
   */
  private function webhook_collections_delete(\stdClass $data) {
    // @todo: Needs functionality.
  }

}
