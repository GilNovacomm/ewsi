<?php

namespace Drupal\investeu_core\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Redirects the node page to another url when a redirect url is available.
 */
class RedirectEventSubscriber implements EventSubscriberInterface {

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * RedirectEventSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $router
   *   The router.
   */
  public function __construct(RouteMatchInterface $router) {
    $this->router = $router;
  }

  /**
   * Redirects the node page to another url when a redirect url is available.
   */
  public function redirectEvent(): void {
    if ($redirect = $this->hasRedirect()) {
      $url = $redirect->first()->getUrl();

      if ($url instanceof Url) {
        $response = new RedirectResponse($url->toString(), 301);
        $response->send();
      }
    }
  }

  /**
   * Gets subscribed events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirectEvent'];

    return $events;
  }

  /**
   * Checks if the current node has a redirect link defined.
   */
  protected function hasRedirect() {
    $node = $this->router->getParameter('node');

    if ($node instanceof NodeInterface
      && $this->router->getRouteName() !== 'entity.node.edit_form'
      && $node->hasField('oe_content_legacy_link')
      && !$node->get('oe_content_legacy_link')->isEmpty()) {
      return $node->get('oe_content_legacy_link');
    }

    return NULL;
  }

}
