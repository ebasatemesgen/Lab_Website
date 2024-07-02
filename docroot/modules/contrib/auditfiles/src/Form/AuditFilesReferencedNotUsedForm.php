<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Form;

use Drupal\auditfiles\Auditor\AuditFilesReferencedNotUsed;
use Drupal\auditfiles\Batch\AuditFilesReferencedNotUsedBatchProcess;
use Drupal\auditfiles\Reference\FileFieldReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Referenced not used.
 *
 * List files referenced in content, but not in the file_usage table.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesReferencedNotUsedForm extends FormBase implements AuditFilesAuditorFormInterface {

  use AuditFilesAuditorFormTrait;

  protected const OPERATION_ADD = 'add';
  protected const OPERATION_DELETE = 'delete';
  protected const TEMPORARY_ALL_REFERENCES = 'references';

  /**
   * Constructs a new AuditFilesReferencedNotUsedForm.
   */
  final public function __construct(
    protected AuditFilesConfigInterface $auditFilesConfig,
    protected AuditFilesReferencedNotUsed $filesReferencedNotUsed,
    protected PagerManagerInterface $pagerManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auditfiles.config'),
      $container->get('auditfiles.auditor.referenced_not_used'),
      $container->get('pager.manager'),
      $container->get('file_url_generator'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_files_referenced_not_used';
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
    return 'audit_files_referenced_not_used';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('auditfiles.reports.referencednotused');
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
    $referenceHash = function (FileFieldReference $reference): string {
      return \sprintf('%s.%s.%d.%s.%s', $reference->table, $reference->column, $reference->entityId, $reference->entityTypeId, $reference->getFileReference()->getId());
    };

    /** @var \Drupal\auditfiles\Reference\FileFieldReference[] $references */
    $references = [];
    foreach ($this->filesReferencedNotUsed->getReferences() as $reference) {
      // Submission will match up checkbox keys with references here.
      $references[$referenceHash($reference)] = $reference;
    }
    $form_state->setTemporaryValue(static::TEMPORARY_ALL_REFERENCES, $references);

    $rows = \array_reduce(
      $references,
      function (?array $rows, FileFieldReference $reference) use ($referenceHash) {
        $row = [
          'file_id' => $reference->getFileReference()->getId(),
          'entity_type' => $reference->getSourceEntityTypeId(),
          'bundle' => [
            'data' => $reference->bundle,
            'hidden' => TRUE,
          ],
          'entity_id' => [
            'data' => $reference->getSourceEntityId(),
            'hidden' => TRUE,
          ],
        ];

        $sourceFile = $this->entityTypeManager
          ->getStorage($reference->getSourceEntityTypeId())
          ->load($reference->getSourceEntityId());
        $row['entity_id_display'] = ($sourceFile !== NULL && $sourceFile->hasLinkTemplate('canonical'))
          ? $sourceFile->toLink(rel: 'canonical')
          : $reference->getSourceEntityId();

        $row['field'] = $reference->table . '.' . $reference->column;
        $row['table'] = [
          'data' => $reference->table,
          'hidden' => TRUE,
        ];

        // If there is a file in the file_managed table, add some of that
        // information to the row, too.
        $file = $reference->getFileReference()->getFile();
        if ($file !== NULL) {
          $row['uri'] = Link::fromTextAndUrl($file->getFileUri(), Url::fromUri(
            $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
            ['attributes' => ['target' => '_blank']],
          ));
          $row['filename'] = [
            'data' => $file->getFilename(),
            'hidden' => TRUE,
          ];
          $row['filemime'] = $file->getMimeType();
          $row['filesize'] = $file->getSize();
        }
        else {
          $row['uri'] = $this->t('Target file was deleted');
          $row['filename'] = ['data' => '', 'hidden' => TRUE];
          $row['filemime'] = '--';
          $row['filesize'] = '--';
        }

        $rows[$referenceHash($reference)] = $row;

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
      'Found 1 file referenced in content that is not in the file_usage table.',
      (($maximumRecords !== 0) ? 'Found at least @count files referenced in content not in the file_usage table.' : 'Found @count files referenced in content not in the file_usage table.'),
    ) : $this->t('Found no files referenced in content not in the file_usage table.');

    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'file_id' => [
          'data' => $this->t('File ID'),
        ],
        'entity_type' => [
          'data' => $this->t('Referencing entity type'),
        ],
        'entity_id_display' => [
          'data' => $this->t('Referencing entity ID'),
        ],
        'field' => [
          'data' => $this->t('Field referenced in'),
        ],
        'uri' => [
          'data' => $this->t('URI'),
        ],
        'filemime' => [
          'data' => $this->t('MIME'),
        ],
        'filesize' => [
          'data' => $this->t('Size (in bytes)'),
        ],
      ],
      '#empty' => $this->t('No items found.'),
      '#options' => $pages[$currentPage] ?? $rows,
    ];

    if (0 === count($rows)) {
      return $form;
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add selected items to the file_usage table'),
      '#validate' => [
        $this::validateListForm(...),
      ],
      '#submit' => [
        $this::submitAddUsageForFileFieldReference(...),
      ],
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected references'),
      '#validate' => [
        $this::validateListForm(...),
      ],
      '#submit' => [
        $this::submitDeleteFileFieldReference(...),
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
    $files = $form_state->getValue('files');
    $files = array_values(array_filter($files));
    $form_state->setValueForElement($form['files'], $files);
    if (0 === count($files)) {
      $form_state->setError($form, $this->t('No items were selected to operate on.'));
    }
  }

  /**
   * Submit form.
   */
  public function submitAddUsageForFileFieldReference(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue('files');
    $references = array_intersect_key(
      $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES),
      array_flip($selected),
    );
    $form_state
      ->setStorage([
        'references' => $references,
        'op' => static::OPERATION_ADD,
        'confirm' => TRUE,
      ])
      ->setRebuild();
  }

  /**
   * Submit form.
   */
  public function submitDeleteFileFieldReference(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue('files');
    $references = array_intersect_key(
      $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES),
      array_flip($selected),
    );
    $form_state
      ->setStorage([
        'references' => $references,
        'op' => static::OPERATION_DELETE,
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

    /** @var array{confirm: true, references: \Drupal\auditfiles\Reference\FileFieldReference[], op: static::OPERATION_*} $storage */
    $storage = &$form_state->getStorage();
    $op = $storage['op'];

    $form['#title'] = match ($op) {
      static::OPERATION_ADD => $this->t('Add these files to the database?'),
      static::OPERATION_DELETE => $this->t('Delete these files from the server?'),
    };

    /** @var \Drupal\auditfiles\Reference\FileFieldReference[] $references */
    $references = $storage['references'];
    $form['changelist'] = [
      '#theme' => 'item_list',
      '#tree' => TRUE,
      '#items' => [],
    ];

    // Prepare the list of items to present to the user.
    foreach ($references as $reference) {
      $tArgs = ['@file' => $reference->getFileReference()->getId()];
      $form['changelist']['#items'][] = match ($op) {
        static::OPERATION_ADD => $this->t('File ID <strong>@file</strong> will be added to the file usage table.', $tArgs),
        static::OPERATION_DELETE => $this->t('File ID <strong>@file</strong> will be deleted from the content.', $tArgs),
      };
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $form;
  }

  /**
   * Delete record from files.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var array{confirm: true, references: \Drupal\auditfiles\Reference\FileFieldReference[], op: static::OPERATION_*} $storage */
    $storage = &$form_state->getStorage();
    $references = $storage['references'];
    $batchDefinition = match ($storage['op']) {
      static::OPERATION_ADD => $this->batchCreateFiles($references),
      static::OPERATION_DELETE => $this->batchDeleteFiles($references),
    };
    $this->batchSet($batchDefinition);
  }

  /**
   * Creates the batch for adding files to the file_usage table.
   *
   * @param \Drupal\auditfiles\Reference\FileFieldReference[] $references
   *   File field references to add.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The definition of the batch.
   */
  private function batchCreateFiles(array $references): BatchBuilder {
    $batch = (new BatchBuilder())
      ->setTitle($this->t('Adding files to the file_usage table'))
      ->setErrorMessage($this->t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesReferencedNotUsedBatchProcess::class, 'finishBatch'])
      ->setProgressMessage($this->t('Completed @current of @total operations.'));
    foreach ($references as $reference) {
      $batch->addOperation(
        [AuditFilesReferencedNotUsedBatchProcess::class, 'createAdd'],
        [$reference],
      );
    }
    return $batch;
  }

  /**
   * Creates the batch for deleting file references from their content.
   *
   * @param \Drupal\auditfiles\Reference\FileFieldReference[] $references
   *   File field references to delete.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The definition of the batch.
   */
  private function batchDeleteFiles(array $references): BatchBuilder {
    $batch = (new BatchBuilder())
      ->setTitle($this->t('Deleting file references from their content'))
      ->setErrorMessage($this->t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesReferencedNotUsedBatchProcess::class, 'finishBatch'])
      ->setProgressMessage($this->t('Completed @current of @total operations.'));
    foreach ($references as $reference) {
      $batch->addOperation(
        [AuditFilesReferencedNotUsedBatchProcess::class, 'createDelete'],
        [$reference],
      );
    }
    return $batch;
  }

  /**
   * Sets the batch.
   */
  protected function batchSet(BatchBuilder $batchDefinition): void {
    \batch_set($batchDefinition->toArray());
  }

}
