<?php

namespace Drupal\purge_invalidation_form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\TypeUnsupportedException;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
use Drupal\purge\Plugin\Purge\Purger\Exception\LockException;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Directly invalidate an item without going through the queue.
 */
class InvalidationManager {
  use StringTranslationTrait;

  /**
   * The 'purge.processors' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * The 'purge.purgers' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * The 'purge.invalidation.factory' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * Construct a InvalidateController object.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The purge invalidation factory service.
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors service.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge purgers service.
   */
  public function __construct(InvalidationsServiceInterface $purge_invalidation_factory, ProcessorsServiceInterface $purge_processors, PurgersServiceInterface $purge_purgers) {
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
    $this->purgeProcessors = $purge_processors;
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * Directly invalidate an item without going through the queue.
   *
   * @param string $type
   *   The type of invalidation to perform, e.g.: tag, url.
   * @param string|null $expression
   *   The string expression of what needs to be invalidated.
   * @param array $options
   *   Associative array of options whose values come from Drush.
   *
   * @example tag node:1
   *   Clears URLs tagged with "node:1" from external caching platforms.
   * @example url http://www.drupal.org/
   *   Clears "http://www.drupal.org/" from external caching platforms.
   */
  public function invalidate($type, $expression = NULL, array $options = ['format' => 'string']) {

    // Retrieve our queuer object and fail when it is not returned.
    if (!($processor = $this->purgeProcessors->get('invalidation_form'))) {
      throw new \Exception($this->t("Please add the required processor:\nInvalidation Form Processor"));
    }

    // Instantiate the invalidation object based on user input.
    try {
      $invalidations = [
        $this->purgeInvalidationFactory->get($type, $expression),
      ];
    }
    catch (PluginNotFoundException $e) {
      throw new \Exception($this->t("Type '@type' does not exist.", ['@type' => $type]));
    }
    catch (InvalidExpressionException $e) {
      throw new \Exception($e->getMessage());
    }
    catch (TypeUnsupportedException $e) {
      throw new \Exception($this->t("There is no purger supporting '@type', please install one!", ['@type' => $type]));
    }
    catch (MissingExpressionException $e) {
      throw new \Exception($e->getMessage());
    }

    // Attempt the cache invalidation and deal with errors.
    try {
      $this->purgePurgers->invalidate($processor, $invalidations);
    }
    catch (DiagnosticsException $e) {
      throw new \Exception($e->getMessage());
    }
    catch (CapacityException $e) {
      throw new \Exception($e->getMessage());
    }
    catch (LockException $e) {
      throw new \Exception($e->getMessage());
    }

    if ($invalidations[0]->getState() != InvStatesInterface::SUCCEEDED) {
      throw new \Exception(
        $this->t('Invalidation failed, return state is: @state.', [
          '@state' => $invalidations[0]->getStateString(),
        ])
      );
    }
  }

}
