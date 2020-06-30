<?php

namespace Drupal\investeu_core\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * RedirectEventSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $router
   *   The router.
   */
  public function __construct(AccountProxyInterface $account, RouteMatchInterface $router) {
    $this->router = $router;
    $this->currentUser = $account;
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
   *
   * Only applies to anonymous users or authenticated
   * users without additional roles.
   */
  protected function hasRedirect() {
    $node = $this->router->getParameter('node');

    if ($node instanceof NodeInterface
      && $this->router->getRouteName() !== 'entity.node.edit_form'
      && ($this->currentUser->isAnonymous() || ($this->currentUser->isAuthenticated() && count($this->currentUser->getRoles()) === 1 && (int) $this->currentUser->id() !== 1))
      && $node->hasField('oe_content_legacy_link')
      && !$node->get('oe_content_legacy_link')->isEmpty()) {
      return $node->get('oe_content_legacy_link');
    }

    return NULL;
  }

}
