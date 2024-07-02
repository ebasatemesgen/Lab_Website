<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Form;

use Drupal\auditfiles\Auditor\AuditFilesNotOnServer;
use Drupal\auditfiles\Batch\AuditFilesDeleteFileEntityBatchProcess;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Not on server.
 *
 * List files in the database, but not in the "files" directory.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesNotOnServerForm extends FormBase implements AuditFilesAuditorFormInterface {

  use AuditFilesAuditorFormTrait;

  protected const TEMPORARY_ALL_REFERENCES = 'references';

  /**
   * Constructs a new AuditFilesNotOnServerForm.
   */
  final public function __construct(
    protected AuditFilesConfigInterface $auditFilesConfig,
    protected AuditFilesNotOnServer $auditFilesNotOnServer,
    protected PagerManagerInterface $pagerManager,
    protected FileSystemInterface $fileSystem,
    protected DateFormatterInterface $dateFormatter,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auditfiles.config'),
      $container->get('auditfiles.auditor.not_on_server'),
      $container->get('pager.manager'),
      $container->get('file_system'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_files_not_on_server';
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
    return 'audit_files_not_on_server';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('auditfiles.reports.notonserver');
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
    /** @var \Drupal\auditfiles\Reference\FileEntityReference[] $references */
    $references = iterator_to_array($this->auditFilesNotOnServer->getReferences());
    $form_state->setTemporaryValue(static::TEMPORARY_ALL_REFERENCES, $references);

    $rows = \array_reduce(
      $references,
      function (?array $rows, FileEntityReference $reference) use ($dateFormat): array {
        $file = $reference->getFile() ?? throw new \LogicException('The file_managed row exists so this should be loadable.');
        $rows[$reference->getId()] = [
          'fid' => $file->id(),
          'uid' => $file->getOwnerId(),
          'filename' => $file->getFilename(),
          'uri' => $file->getFileUri(),
          'path' => $this->fileSystem->realpath($file->getFileUri()),
          'filemime' => $file->getMimeType(),
          'filesize' => $file->getSize() ? \number_format((float) $file->getSize()) : NULL,
          'datetime' => $this->dateFormatter->format($file->getCreatedTime(), $dateFormat),
          'status' => $file->isPermanent() ? $this->t('Permanent') : $this->t('Temporary'),
        ];
        return $rows;
      },
      [],
    );

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
      'Found 1 file entity with missing file on disk.',
      (($maximumRecords !== 0) ? 'Found at least @count file entities with missing files on disk.' : 'Found @count file entities with missing files on disk.'),
    ) : $this->t('Found no file entities with missing files on disk.');

    // Create the form table.
    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'fid' => [
          'data' => $this->t('File ID'),
        ],
        'uid' => [
          'data' => $this->t('User ID'),
        ],
        'filename' => [
          'data' => $this->t('Name'),
        ],
        'uri' => [
          'data' => $this->t('URI'),
        ],
        'path' => [
          'data' => $this->t('Path'),
        ],
        'filemime' => [
          'data' => $this->t('MIME'),
        ],
        'filesize' => [
          'data' => $this->t('Size'),
        ],
        'datetime' => [
          'data' => $this->t('When added'),
        ],
        'status' => [
          'data' => $this->t('Status'),
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
      '#value' => $this->t('Delete selected items from the database'),
      '#validate' => [
        $this::validateListForm(...),
      ],
      '#submit' => [
        $this::submissionHandlerDeleteFromDb(...),
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
   * Delete record to database.
   */
  public function submissionHandlerDeleteFromDb(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue('files');
    $references = array_filter(
      $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES),
      static function (FileEntityReference $reference) use ($selected): bool {
        return array_key_exists($reference->getId(), $selected);
      },
    );
    $form_state
      ->setStorage([
        'references' => $references,
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
    $form['#title'] = $this->t('Delete these items from the database?');

    /** @var array{confirm: true|null, files: string[], references: \Drupal\auditfiles\Reference\FileEntityReference[]} $storage */
    $storage = &$form_state->getStorage();

    $references = $storage['references'];
    $form['changelist'] = [
      '#theme' => 'item_list',
      '#tree' => TRUE,
      '#items' => [],
    ];

    // Prepare the list of items to present to the user.
    foreach ($references as $reference) {
      $tArgs = ['@file' => $reference->getFile()->getFilename()];
      $form['changelist']['#items'][] = $this->t('<strong>@file</strong> and all usages will be deleted from the database.', $tArgs);
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
   * Delete record from database confirm.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var array{confirm: true|null, references: \Drupal\auditfiles\Reference\FileEntityReference[]} $storage */
    $storage = &$form_state->getStorage();
    $references = $storage['references'];

    $batchDefinition = (new BatchBuilder())
      ->setTitle($this->t('Deleting files from the database'))
      ->setErrorMessage($this->t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesDeleteFileEntityBatchProcess::class, 'finishBatch'])
      ->setProgressMessage($this->t('Completed @current of @total operations.'));
    foreach ($references as $reference) {
      $batchDefinition->addOperation(
        [AuditFilesDeleteFileEntityBatchProcess::class, 'create'],
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
