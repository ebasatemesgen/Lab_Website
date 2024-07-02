<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Form;

use Drupal\auditfiles\Auditor\AuditFilesUsedNotManaged;
use Drupal\auditfiles\Batch\AuditFilesDeleteFileUsageBatchProcess;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Reference\FileUsageReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Usage with missing files.
 *
 * Lists entries in the file usage table, where the file entity does not exist.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesUsedNotManagedForm extends FormBase implements AuditFilesAuditorFormInterface {

  use AuditFilesAuditorFormTrait;

  protected const TEMPORARY_ALL_REFERENCES = 'references';

  /**
   * Constructs a new AuditFilesUsedNotManagedForm.
   */
  final public function __construct(
    protected AuditFilesConfigInterface $auditFilesConfig,
    protected AuditFilesUsedNotManaged $filesUsedNotManaged,
    protected PagerManagerInterface $pagerManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auditfiles.config'),
      $container->get('auditfiles.auditor.used_not_managed'),
      $container->get('pager.manager'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_files_used_not_managed';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): TranslatableMarkup {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName(): string {
    return 'audit_files_used_not_managed';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('auditfiles.reports.usednotmanaged');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Do you want to delete following record');
  }

  /**
   * {@inheritdoc}
   */
  public function buildListForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\auditfiles\Reference\FileUsageReference[] $references */
    $references = iterator_to_array($this->filesUsedNotManaged->getReferences());
    $form_state->setTemporaryValue(static::TEMPORARY_ALL_REFERENCES, $references);

    $rows = \array_reduce(
      $references,
      function (?array $rows, FileUsageReference $reference): array {
        try {
          $source = $this->entityTypeManager
            ->getStorage($reference->getEntityTypeId())
            ->load($reference->getEntityId());
        }
        catch (PluginNotFoundException) {
        }

        $sourceLink = ($source ?? NULL)?->toLink(
          text: sprintf('%s/%s', $reference->getEntityTypeId(), $reference->getEntityId()),
        )->toString() ?? $reference->getEntityId();
        $rows[$reference->getFileId()] = [
          'fid' => $reference->getFileId(),
          'module' => $this->t('@module module', ['@module' => $reference->getModule()]),
          'id' => $sourceLink,
          'count' => $reference->getCount(),
        ];

        return $rows;
      },
      // Handle when there is nothing generated.
    ) ?? [];

    $pages = [];
    $currentPage = NULL;
    if (count($rows) > 0) {
      $itemsPerPage = $this->auditFilesConfig->getReportOptionsItemsPerPage();
      if ($itemsPerPage > 0) {
        $currentPage = $this->pagerManager->createPager(count($rows), $itemsPerPage)->getCurrentPage();
        $pages = array_chunk($rows, $itemsPerPage, TRUE);
      }
    }

    // Setup the record count and related messages.
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    $form['help']['#markup'] = (count($rows) > 0) ? $this->formatPlural(
      count($rows),
      'Found 1 entries in the file usage table where file entity is missing.',
      (($maximumRecords !== 0) ? 'Found at least @count entries in the file usage table where file entity is missing.' : 'Found @count entries in the file usage table where file entity is missing.'),
    ) : $this->t('Found no entries in the file usage table where file entity is missing.');

    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'fid' => [
          'data' => $this->t('Missing-File ID'),
        ],
        'module' => [
          'data' => $this->t('Used by'),
        ],
        'id' => [
          'data' => $this->t('Used in'),
        ],
        'count' => [
          'data' => $this->t('Count'),
        ],
      ],
      '#empty' => $this->t('No items found.'),
      '#options' => $pages[$currentPage] ?? $rows,
    ];

    if (0 === count($rows)) {
      return $form;
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected items from the file_usage table'),
      '#validate' => [
        $this::validateListForm(...),
      ],
      '#submit' => [
        $this::submitForm(...),
      ],
    ];
    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * Validate list form.
   */
  public function validateListForm(array &$form, FormStateInterface $form_state): void {
    /** @var array<string, string|0> $files */
    $files = array_filter($form_state->getValue('files'));
    $form_state->setValueForElement($form['files'], $files);
    if (0 === count($files)) {
      $form_state->setError($form, $this->t('No items were selected to operate on.'));
    }
  }

  /**
   * Submit for confirmation.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue('files');
    /** @var \Drupal\auditfiles\Reference\FileUsageReference[] $references */
    $references = array_filter(
      $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES),
      static function (FileUsageReference $reference) use ($selected): bool {
        return array_key_exists($reference->getFileId(), $selected);
      },
    );

    // Re-make these references into file references and deduplicate.
    $newReferences = [];
    foreach ($references as $reference) {
      $new = FileEntityReference::create($reference->getFileId());
      $newReferences[$new->getId()] = $new;
    }

    $form_state
      ->setStorage([
        'references' => $newReferences,
        'confirm' => TRUE,
      ])
      ->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfirmForm(array $form, FormStateInterface $form_state): array {
    $form['#theme'] = 'confirm_form';
    $form['#attributes']['class'][] = 'confirmation';
    $form['#title'] = $this->t('Delete these items from the file_usage table?');

    /** @var array{confirm: true|null, references: \Drupal\auditfiles\Reference\FileEntityReference[]} $storage */
    $storage = &$form_state->getStorage();

    $form['changelist'] = [
      '#theme' => 'item_list',
      '#tree' => TRUE,
      '#items' => [],
    ];

    foreach ($storage['references'] as $reference) {
      $tArgs = ['@file' => $reference->getId()];
      $form['changelist']['#items'][] = $this->t('File ID <strong>@file</strong> will be deleted from the file_usage table.', $tArgs);
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
      '#submit' => [
        [$this, 'submitDeleteFileUsage'],
      ],
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $form;
  }

  /**
   * Submit handler for confirmation.
   */
  public function submitDeleteFileUsage(array &$form, FormStateInterface $form_state): void {
    /** @var array{confirm: true|null, references: \Drupal\auditfiles\Reference\FileEntityReference[]} $storage */
    $storage = $form_state->getStorage();
    $references = $storage['references'];

    $batchDefinition = (new BatchBuilder())
      ->setTitle(\t('Deleting files from the file_usage table'))
      ->setErrorMessage(\t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesDeleteFileUsageBatchProcess::class, 'finishBatch'])
      ->setProgressMessage(\t('Completed @current of @total operations.'));
    foreach ($references as $reference) {
      $batchDefinition->addOperation(
        [AuditFilesDeleteFileUsageBatchProcess::class, 'create'],
        [$reference],
      );
    }

    $this->batchSet($batchDefinition);
  }

  /**
   * Sets the batch.
   */
  protected function batchSet(BatchBuilder $batchDefinition): void {
    \batch_set($batchDefinition->toArray());
  }

}
