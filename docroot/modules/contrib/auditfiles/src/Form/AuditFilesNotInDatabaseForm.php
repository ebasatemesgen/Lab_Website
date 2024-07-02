<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Form;

use Drupal\auditfiles\AuditFilesAuditorInterface;
use Drupal\auditfiles\Batch\AuditFilesNotInDatabaseBatchProcess;
use Drupal\auditfiles\Reference\DiskReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Not in database.
 *
 * Allows for comparing and correcting files and file references in the "files"
 * directory, in the database, and in content.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesNotInDatabaseForm extends FormBase implements AuditFilesAuditorFormInterface {

  use AuditFilesAuditorFormTrait;

  protected const OPERATION_ADD = 'add';
  protected const OPERATION_DELETE = 'delete';
  protected const TEMPORARY_ALL_REFERENCES = 'references';

  /**
   * Constructs a new AuditFilesNotInDatabaseForm.
   *
   * @phpstan-param \Drupal\auditfiles\AuditFilesAuditorInterface<\Drupal\auditfiles\Reference\DiskReference> $auditor
   */
  final public function __construct(
    private AuditFilesConfigInterface $auditFilesConfig,
    private AuditFilesAuditorInterface $auditor,
    private PagerManagerInterface $pagerManager,
    RequestStack $requestStack,
    private DateFormatterInterface $dateFormatter,
    private MimeTypeGuesserInterface $mimeTypeGuesser,
  ) {
    $this->setRequestStack($requestStack);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auditfiles.config'),
      $container->get('auditfiles.auditor.not_in_database'),
      $container->get('pager.manager'),
      $container->get('request_stack'),
      $container->get('date.formatter'),
      $container->get('file.mime_type.guesser'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'notindatabase';
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
    return 'audit_files_not_in_database';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('auditfiles.reports.notindatabase');
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
    $dateFormat = $this->auditFilesConfig->getReportOptionsDateFormat();
    /** @var \Drupal\auditfiles\Reference\DiskReference[] $references */
    $references = iterator_to_array($this->auditor->getReferences());
    $form_state->setTemporaryValue(static::TEMPORARY_ALL_REFERENCES, $references);

    $rows = \array_reduce(
      $references,
      function (?array $rows, DiskReference $reference) use ($dateFormat): array {
        $rows[$reference->getUri()] = [
          'filepathname' => $reference->getUri(),
          'filemime' => $this->mimeTypeGuesser->guessMimeType($reference->getUri()) ?? '',
          'filesize' => filesize($reference->getUri()) ?: '',
          'filemodtime' => $this->dateFormatter->format(filemtime($reference->getUri()), $dateFormat),
        ];
        return $rows;
      },
      [],
    );

    $pages = [];
    $currentPage = NULL;
    if (count($rows) > 0) {
      // Set up the pager.
      $itemsPerPage = $this->auditFilesConfig->getReportOptionsItemsPerPage();
      if ($itemsPerPage > 0) {
        $currentPage = $this->pagerManager->createPager(count($rows), $itemsPerPage)->getCurrentPage();
        // Break the total data set into page sized chunks.
        $pages = array_chunk($rows, $itemsPerPage, TRUE);
      }
    }

    // Define the form.
    // Setup the record count and related messages.
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    $form['help']['#markup'] = (count($rows) > 0) ? $this->formatPlural(
      count($rows),
      'Found 1 file on disk that is not in the database.',
      (($maximumRecords !== 0) ? 'Found at least @count files on disk but not in the database.' : 'Found @count files on disk not in the database.'),
    ) : $this->t('Found no files on disk not in the database.');

    // Create the form table.
    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'filepathname' => [
          'data' => $this->t('File pathname'),
        ],
        'filemime' => [
          'data' => $this->t('MIME'),
        ],
        'filesize' => [
          'data' => $this->t('Size (in bytes)'),
        ],
        'filemodtime' => [
          'data' => $this->t('Last modified'),
        ],
      ],
      '#empty' => $this->t('No items found.'),
      '#options' => $pages[$currentPage] ?? $rows,
      '#required' => TRUE,
    ];

    if (0 === count($rows)) {
      return $form;
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add selected items to the database'),
      '#validate' => [
        $this::validateListForm(...),
      ],
      '#submit' => [
        $this::submitAddRecord(...),
      ],
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected items from the server'),
      '#validate' => [
        $this::validateListForm(...),
      ],
      '#submit' => [
        $this::submitDeleteRecord(...),
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
    $files = array_filter($files);
    $form_state->setValueForElement($form['files'], $files);
    if (0 === count($files)) {
      $form_state->setError($form, $this->t('No items were selected to operate on.'));
    }
  }

  /**
   * Add record to database.
   */
  public function submitAddRecord(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue('files');
    $references = array_filter(
      $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES),
      static function (DiskReference $diskReference) use ($selected): bool {
        return in_array($diskReference->getUri(), $selected, TRUE);
      },
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
   * Delete record from files.
   */
  public function submitDeleteRecord(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue('files');
    $references = array_filter(
      $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES),
      static function (DiskReference $diskReference) use ($selected): bool {
        return in_array($diskReference->getUri(), $selected, TRUE);
      },
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

    /** @var array{confirm: true, references: \Drupal\auditfiles\Reference\DiskReference[], op: static::OPERATION_*} $storage */
    $storage = &$form_state->getStorage();
    $op = $storage['op'];

    $form['#title'] = match ($op) {
      static::OPERATION_ADD => $this->t('Add these files to the database?'),
      static::OPERATION_DELETE => $this->t('Delete these files from the server?'),
    };

    /** @var \Drupal\auditfiles\Reference\DiskReference[] $references */
    $references = $storage['references'];
    $form['changelist'] = [
      '#theme' => 'item_list',
      '#tree' => TRUE,
      '#items' => [],
    ];

    // Prepare the list of items to present to the user.
    foreach ($references as $reference) {
      $tArgs = ['@file' => $reference->getUri()];
      $form['changelist']['#items'][] = match ($op) {
        static::OPERATION_ADD => $this->t('<strong>@file</strong> will be added to the database.', $tArgs),
        static::OPERATION_DELETE => $this->t('<strong>@file</strong> will be deleted from the server.', $tArgs),
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
   * Execute the operation via batch.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var array{confirm: true|null, references: \Drupal\auditfiles\Reference\DiskReference[], op: string} $storage */
    $storage = &$form_state->getStorage();
    $references = $storage['references'];
    $batchDefinition = match ($storage['op']) {
      static::OPERATION_ADD => $this->batchCreateFiles($references),
      static::OPERATION_DELETE => $this->batchDeleteFiles($references),
      default => throw new \LogicException('Unknown operation'),
    };
    $this->batchSet($batchDefinition);
  }

  /**
   * Creates the batch for adding files to the database.
   *
   * @param \Drupal\auditfiles\Reference\DiskReference[] $references
   *   Disk references to add.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The definition of the batch.
   */
  private function batchCreateFiles(array $references): BatchBuilder {
    $batch = (new BatchBuilder())
      ->setTitle(\t('Adding files to Drupal file management'))
      ->setErrorMessage(\t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesNotInDatabaseBatchProcess::class, 'finishBatch'])
      ->setProgressMessage(\t('Completed @current of @total operations.'));
    foreach ($references as $reference) {
      $batch->addOperation(
        [AuditFilesNotInDatabaseBatchProcess::class, 'createAdd'],
        [$reference],
      );
    }
    return $batch;
  }

  /**
   * Creates the batch for deleting files from disk.
   *
   * @param \Drupal\auditfiles\Reference\DiskReference[] $references
   *   Disk references to delete.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The definition of the batch.
   */
  private function batchDeleteFiles(array $references): BatchBuilder {
    $batch = (new BatchBuilder())
      ->setTitle(\t('Deleting files from the server'))
      ->setErrorMessage(\t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesNotInDatabaseBatchProcess::class, 'finishBatch'])
      ->setProgressMessage(\t('Completed @current of @total operations.'));
    foreach ($references as $reference) {
      $batch->addOperation(
        [AuditFilesNotInDatabaseBatchProcess::class, 'createDelete'],
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
