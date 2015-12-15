<?php
/**
 * @file
 * Contains taxonomy term redirect subscriber functionality.
 */
namespace Drupal\shopify\Event;

use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ShopifyWebhookSubscriber
 *
 * Provides the webhook subscriber functionality.
 */
class ShopifyTermRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Redirects shopify tag/collection taxonomy terms to the right page.
   *
   * @todo: Not sure this is the best way of doing things.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    if ($term = $event->getRequest()->get('taxonomy_term')) {
      if ($term instanceof Term && $term->bundle() == ShopifyProduct::SHOPIFY_TAGS_VID) {
        $event->setResponse(new RedirectResponse('/' . shopify_store_url() . '/tag/' . $term->id()));
      }
    }
    if ($term = $event->getRequest()->get('taxonomy_term')) {
      if ($term instanceof Term && $term->bundle() == ShopifyProduct::SHOPIFY_COLLECTIONS_VID) {
        $event->setResponse(new RedirectResponse('/' . shopify_store_url() . '/collection/' . $term->id()));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
  }


}
