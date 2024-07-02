<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Form;

use Drupal\auditfiles\Auditor\AuditFilesManagedNotUsed;
use Drupal\auditfiles\Batch\AuditFilesDeleteFileEntityBatchProcess;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
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
 * Managed not used.
 *
 * Lists files in the file_managed table, but not in the file_usage
 * table.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesManagedNotUsedForm extends FormBase implements AuditFilesAuditorFormInterface {

  use AuditFilesAuditorFormTrait;

  protected const TEMPORARY_ALL_REFERENCES = 'references';

  /**
   * Constructs a new AuditFilesManagedNotUsedForm.
   */
  final public function __construct(
    protected AuditFilesConfigInterface $auditFilesConfig,
    protected AuditFilesManagedNotUsed $filesManagedNotUsed,
    protected PagerManagerInterface $pagerManager,
    protected DateFormatterInterface $dateFormatter,
    protected FileSystemInterface $fileSystem,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auditfiles.config'),
      $container->get('auditfiles.auditor.managed_not_used'),
      $container->get('pager.manager'),
      $container->get('date.formatter'),
      $container->get('file_system'),
      $container->get('file_url_generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_files_managed_not_used';
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
    return 'audit_files_managed_not_used_service';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('auditfiles.reports.managednotused');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t("Do you want to delete following record");
  }

  /**
   * {@inheritdoc}
   */
  public function buildListForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\auditfiles\Reference\FileEntityReference[] $references */
    $references = iterator_to_array($this->filesManagedNotUsed->getReferences());
    $form_state->setTemporaryValue(static::TEMPORARY_ALL_REFERENCES, $references);
    $rows = [];

    foreach ($references as $reference) {
      $file = $reference->getFile() ?? throw new \LogicException('The file_managed row exists so this should be loadable.');
      $dateFormat = $this->auditFilesConfig->getReportOptionsDateFormat();
      $rows[$reference->getId()] = [
        'fid' => $file->id(),
        'uid' => $file->getOwnerId() ?? 'ss',
        'filename' => $file->getFilename(),
        'uri' => Link::fromTextAndUrl($file->getFileUri(), Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()), ['attributes' => ['target' => '_blank']])),
        'path' => $this->fileSystem->realpath($file->getFileUri()),
        'filemime' => $file->getMimeType(),
        'filesize' => $file->getSize() ? \number_format((float) $file->getSize()) : '',
        'datetime' => $this->dateFormatter->format($file->getCreatedTime(), $dateFormat),
        'status' => $file->isPermanent() ? $this->t('Permanent') : $this->t('Temporary'),
      ];
    }

    $pages = [];
    $currentPage = NULL;
    $rows_count = count($rows);
    if ($rows_count > 0) {
      $itemsPerPage = $this->auditFilesConfig->getReportOptionsItemsPerPage();
      if ($itemsPerPage > 0) {
        $currentPage = $this->pagerManager->createPager($rows_count, $itemsPerPage)->getCurrentPage();
        $pages = array_chunk($rows, $itemsPerPage, TRUE);
      }
    }

    // Setup the record count and related messages.
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    $form['help']['#markup'] = ($rows_count > 0) ? $this->formatPlural(
      $rows_count,
      'Found 1 file in the file_managed table that is not in the file_usage table.',
      (($maximumRecords !== 0) ? 'Found at least @count files in the file_managed table not in the file_usage table.' : 'Found @count files in the file_managed table not in the file_usage table.'),
    ) : $this->t('Found no files in the file_managed table not in the file_usage table.');

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

    if (0 === $rows_count) {
      return $form;
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected items from the file_managed table'),
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
    $files = array_filter($form_state->getValue('files'));
    $form_state->setValueForElement($form['files'], $files);
    if (0 === count($files)) {
      $form_state->setError($form, $this->t('No items were selected to operate on.'));
    }
  }

  /**
   * Delete record from files.
   */
  public function submitDeleteRecord(array &$form, FormStateInterface $form_state): void {
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
    $form['#title'] = $this->t('Delete these items from the file_managed table?');

    /** @var array{confirm: true|null, references: \Drupal\auditfiles\Reference\FileEntityReference[]} $storage */
    $storage = &$form_state->getStorage();

    $form['changelist'] = [
      '#theme' => 'item_list',
      '#tree' => TRUE,
      '#items' => [],
    ];

    foreach ($storage['references'] as $reference) {
      $tArgs = ['@file' => $reference->getFile()->getFilename()];
      $form['changelist']['#items'][] = $this->t('<strong>@file</strong> will be deleted from the file_managed table.', $tArgs);
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
   * Submit form delete file managed record.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var array{confirm: true|null, references: \Drupal\auditfiles\Reference\FileEntityReference[]} $storage */
    $storage = &$form_state->getStorage();
    $references = $storage['references'];

    $batchDefinition = (new BatchBuilder())
      ->setTitle(\t('Deleting files from the file_managed table'))
      ->setErrorMessage(\t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesDeleteFileEntityBatchProcess::class, 'finishBatch'])
      ->setProgressMessage(\t('Completed @current of @total operations.'));
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
