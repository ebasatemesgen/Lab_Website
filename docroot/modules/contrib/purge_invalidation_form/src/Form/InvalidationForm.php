<?php

namespace Drupal\purge_invalidation_form\Form;

use Consolidation\Log\ConsoleLogLevel;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge_invalidation_form\InvalidationManager;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Directly invalidate an item without going through the queue using a form.
 */
class InvalidationForm extends FormBase {

  /**
   * Manages Purge invalidations.
   *
   * @var \Drupal\purge_invalidation_form\InvalidationManager
   */
  protected $invalidationManager;

  /**
   * The logger to use.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a InvalidationForm.
   *
   * @param Drupal\purge_invalidation_form\InvalidationManager $invalidationManager
   *   Manages Purge invalidations.
   * @param \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(InvalidationManager $invalidationManager, LoggerChannelInterface $logger, MessengerInterface $messenger) {
    $this->invalidationManager = $invalidationManager;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('purge_invalidation_form.invalidation_manager'),
      $container->get('logger.channel.purge_invalidation_form'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purge_invalidation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['information'] = [
      '#markup' => $this->t('Directly invalidate items without going through the purge queue.'),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => t('The type of invalidation to perform: tag or url.'),
      '#options' => [
        'url' => $this->t('URL'),
        'tag' => $this->t('Tag'),
      ],
      '#default_value' => 'url',
      '#required' => TRUE,
    ];

    $form['items'] = [
      '#title' => t('Item'),
      '#type' => 'textarea',
      '#description' => $this->t("The item(s) that needs to be invalidated, one per line."),
      '#placeholder' => $this->t("Example:\nhttps://domain.com/image.png\nhttps://domain.com/path/"),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Purge'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $type = $values['type'];
    $items = explode("\n", $values['items']);

    try {
      foreach ($items as $item) {
        if (!empty(trim($item))) {
          $this->invalidationManager->invalidate($type, trim($item));
          $message = t('@item invalidated successfully!', ['@item' => $item]);
          $this->logger->notice($message);
          $this->messenger->addStatus($message);
        };
      }
    }
    catch (\Exception $e) {
      $message = $this->t('Exception while trying to invalidate the items: @error', ['@error' => $e->getMessage()]);
      $this->logger->error($message);
      $this->messenger->addError($message);
    }
  }

}
