<?php

namespace Drupal\lightning_api;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\lightning_api\Form\OAuthKeyForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to request-related events.
 *
 * @internal
 *   This is an internal part of Lightning API and may be changed or removed at
 *   any time without warning. External code should not use this class.
 */
final class RequestSubscriber implements EventSubscriberInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  private $classResolver;

  /**
   * RequestSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver service.
   */
  public function __construct(RouteMatchInterface $route_match, ClassResolverInterface $class_resolver) {
    $this->routeMatch = $route_match;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'onRequest',
    ];
  }

  /**
   * Reacts when a request begins.
   */
  public function onRequest(): void {
    if ($this->routeMatch->getRouteName() !== 'oauth2_token.settings') {
      return;
    }

    /** @var \Drupal\lightning_api\Form\OAuthKeyForm $form */
    $form = $this->classResolver->getInstanceFromDefinition(OAuthKeyForm::class);
    if ($form->keyExists() === FALSE) {
      $warning = $this->t('You may wish to <a href=":generate_keys">generate a key pair</a> for OAuth authentication.', [
        ':generate_keys' => Url::fromRoute('lightning_api.generate_keys')->toString(),
      ]);
      $this->messenger()->addWarning($warning);
    }
  }

}
