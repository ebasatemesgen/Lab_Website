<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Form;

use Drupal\auditfiles\Auditor\AuditFilesMergeFileReferences;
use Drupal\auditfiles\Batch\AuditFilesMergeFileReferencesBatchProcess;
use Drupal\auditfiles\Reference\FileEntityReference;
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

/**
 * Merge file references.
 *
 * Lists potential duplicate files in the file_managed table, and allows for
 * merging them into a single file.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesMergeFileReferencesForm extends FormBase implements AuditFilesAuditorFormInterface {

  protected const STAGE_CONFIRM = 'confirm';
  protected const STAGE_PRECONFIRM = 'preconfirm';
  protected const TEMPORARY_ALL_REFERENCES = 'references';

  /**
   * Constructs a new AuditFilesMergeFileReferencesForm.
   */
  final public function __construct(
    protected AuditFilesConfigInterface $auditFilesConfig,
    protected PagerManagerInterface $pagerManager,
    protected AuditFilesMergeFileReferences $mergeFileReferences,
    protected DateFormatterInterface $dateFormatter,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auditfiles.config'),
      $container->get('pager.manager'),
      $container->get('auditfiles.auditor.merge_file_references'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mergefilereferences';
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
    return 'audit_files_merge_file_references';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('auditfiles.reports.mergefilereferences');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Do you want to merge following record');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var array{confirm: true|null, files: string[], op: string, stage: string} $storage */
    $storage = $form_state->getStorage();
    return match (TRUE) {
      // State is "preconfirm", build form and form submit handler.
      (isset($storage['confirm']) && $storage['stage'] == static::STAGE_PRECONFIRM) => $this->buildPreConfirmForm($form, $form_state),
      // Stage is "confirm", build form and form submit handler.
      (isset($storage['confirm']) && $storage['stage'] == static::STAGE_CONFIRM) => $this->buildConfirmForm($form, $form_state),
      // The initial list form.
      default => $this->buildListForm($form, $form_state),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function buildListForm(array $form, FormStateInterface $form_state): array {
    $allReferences = [];
    foreach ($this->mergeFileReferences->getReferences() as $reference) {
      assert($reference instanceof FileEntityReference);
      $allReferences[$reference->getFile()->getFilename()][] = $reference;
    }
    $form_state->setTemporaryValue(static::TEMPORARY_ALL_REFERENCES, $allReferences);

    $rows = array_map(function (array $references) {
      // $references is at least two items.
      /** @var \Drupal\auditfiles\Reference\FileEntityReference[] $references */

      $row = [
        'filename' => NULL,
        'references' => [
          'data' => [
            '#theme' => 'item_list',
            '#tree' => TRUE,
            '#items' => [],
          ],
        ],
      ];

      foreach ($references as $reference) {
        $file = $reference->getFile() ?? throw new \LogicException('File should exist as row is in database.');
        // Filename should be the same in each iteration.
        $row['filename'] = $reference->getFile()->getFilename();
        $row['references']['data']['#items'][] = [
          '#type' => 'inline_template',
          '#template' => '<strong>Fid: </strong> {{ id }} , <strong>Name : </strong> {{ file }} , <strong>File URI: </strong> {{ uri }} , <strong>Date: </strong> {{ date }}',
          '#context' => [
            'id' => $file->id(),
            'file' => $file->getFilename(),
            'uri' => $file->getFileUri(),
            'date' => $this->dateFormatter->format($file->getCreatedTime(), $this->auditFilesConfig->getReportOptionsDateFormat()),
          ],
        ];
      }

      return $row;
    }, $allReferences);

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

    // Setup the record count and related messages.
    $maximumRecords = $this->auditFilesConfig->getReportOptionsMaximumRecords();
    $form['help']['#markup'] = (count($rows) > 0) ? $this->formatPlural(
      count($rows),
      'Found 1 file in the file_managed table with a duplicate file name.',
      (($maximumRecords !== 0) ? 'Found at least @count files in the file_managed table with duplicate file names.' : 'Found @count files in the file_managed table with duplicate file names.'),
    ) : $this->t('Found no files in the file_managed table with duplicate file names.');

    $form['filter']['single_file_names']['auditfiles_merge_file_references_show_single_file_names'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show files without duplicate names'),
      '#default_value' => $this->auditFilesConfig->isMergeFileReferencesShowSingleFileNames(),
      '#description' => $this->t("Use this button to reset this report's variables and load the page anew."),
    ];

    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'filename' => [
          'data' => $this->t('Name'),
        ],
        'references' => [
          'data' => $this->t('File IDs using the filename'),
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
      '#value' => $this->t('Merge selected items'),
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
    $form_state->setValueForElement($form['files'], array_values($files));
    if (0 === count($files)) {
      $form_state->setError($form, $this->t('At least one file name must be selected in order to merge the file IDs. No changes were made.'));
    }
  }

  /**
   * Submit form handler for Merge Records.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // @todo this should be moved to configuration form.
    $this->configFactory()->getEditable('auditfiles.settings')
      ->set('auditfiles_merge_file_references_show_single_file_names', $form_state->getValue('auditfiles_merge_file_references_show_single_file_names'))
      ->save();

    $form_state
      ->setStorage([
        'all_references' => $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES),
        'files' => $form_state->getValue('files'),
        'confirm' => TRUE,
        'stage' => static::STAGE_PRECONFIRM,
      ])
      ->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfirmForm(array $form, FormStateInterface $form_state): array {
    /** @var array<string, \Drupal\auditfiles\Reference\FileEntityReference[]> $allReferences */
    $allReferences = $form_state->getTemporaryValue(static::TEMPORARY_ALL_REFERENCES);
    $dateFormat = $this->auditFilesConfig->getReportOptionsDateFormat();
    $files = [];

    /** @var string[] $values */
    $values = $form_state->getValue('files');
    foreach ($values as $fileName) {
      foreach ($allReferences[$fileName] as $reference) {
        $file = $reference->getFile() ?? throw new \LogicException('File should exist');
        $files[$file->id()] = [
          'filename' => $file->getFilename(),
          'fileid' => $file->id(),
          'fileuri' => $file->getFileUri(),
          'filesize' => \number_format((float) $file->getSize()),
          'timestamp' => $this->dateFormatter->format($file->getCreatedTime(), $dateFormat),
        ];
      }
    }

    $form['files_being_merged'] = [
      '#type' => 'tableselect',
      '#header' => [
        'filename' => [
          'data' => $this->t('Filename'),
        ],
        'fileid' => [
          'data' => $this->t('File ID'),
        ],
        'fileuri' => [
          'data' => $this->t('URI'),
        ],
        'filesize' => [
          'data' => $this->t('Size'),
        ],
        'timestamp' => [
          'data' => $this->t('Time uploaded'),
        ],
      ],
      '#options' => $files,
      '#empty' => $this->t('No items found.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next step'),
      '#validate' => [
        $this::validateForm(...),
      ],
      '#submit' => [
        $this::submitMergePreConfirm(...),
      ],
    ];

    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    /** @var array{files: string[], confirm: true|null, stage?: string} $storage */
    $storage = $form_state->getStorage();
    if (static::STAGE_PRECONFIRM === ($storage['stage'] ?? NULL)) {
      /** @var string[] $fileEntityIdsBeingMerged */
      $fileEntityIdsBeingMerged = array_filter($form_state->getValue('files_being_merged'));
      $form_state->setValueForElement($form['files_being_merged'], array_map('intval', array_values($fileEntityIdsBeingMerged)));
      if (count($fileEntityIdsBeingMerged) < 2) {
        $form_state->setError($form['files_being_merged'], $this->t('At least two files must be selected in order to merge them.'));
      }
    }
  }

  /**
   * Preconfirm form submission.
   */
  public function submitMergePreConfirm(array &$form, FormStateInterface $form_state): void {
    // Always has at least two IDs.
    /** @var int[] $fileEntityIdsBeingMerged */
    $fileEntityIdsBeingMerged = array_filter($form_state->getValue('files_being_merged'));

    $form_state
      ->setStorage([
        'all_references' => $form_state->getStorage()['all_references'] ?? throw new \LogicException('Missing all references'),
        'files_being_merged' => $fileEntityIdsBeingMerged,
        'confirm' => TRUE,
        'stage' => static::STAGE_CONFIRM,
      ])
      ->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfirmForm(array $form, FormStateInterface $form_state): array {
    $form['#title'] = $this->t('Which file will be the one the others are merged into?');
    $form['#attributes']['class'][] = 'confirmation';
    $form['#theme'] = 'confirm_form';

    /** @var array<string, \Drupal\auditfiles\Reference\FileEntityReference[]> $allReferences */
    $allReferences = $form_state->getStorage()['all_references'] ?? throw new \LogicException('Missing all references');

    // Flatten file ID -> file reference object.
    /** @var array<int, \Drupal\auditfiles\Reference\FileEntityReference> $files */
    $files = [];
    array_walk_recursive($allReferences, function (FileEntityReference $reference) use (&$files): void {
      $files[$reference->getId()] = $reference;
    });

    // Always contains at least two values.
    /** @var int[] $fileIds */
    $fileIds = $form_state->getValue('files_being_merged');
    $options = [];
    foreach ($fileIds as $fileId) {
      $reference = $files[$fileId] ?? throw new \LogicException('File reference disappeared.');
      $file = $reference->getFile() ?? throw new \LogicException('File entity disappeared');
      $options[$fileId] = [
        'origname' => $file->getFilename(),
        'fileid' => $file->id(),
        'fileuri' => $file->getFileUri(),
        'filesize' => \number_format((float) $file->getSize()),
        'timestamp' => $this->dateFormatter->format($file->getCreatedTime(), $this->auditFilesConfig->getReportOptionsDateFormat()),
      ];
    }

    $form['file_being_kept'] = [
      '#type' => 'tableselect',
      '#header' => [
        'origname' => [
          'data' => $this->t('Filename'),
        ],
        'fileid' => [
          'data' => $this->t('File ID'),
        ],
        'fileuri' => [
          'data' => $this->t('URI'),
        ],
        'filesize' => [
          'data' => $this->t('Size'),
        ],
        'timestamp' => [
          'data' => $this->t('Time uploaded'),
        ],
      ],
      '#options' => $options,
      '#default_value' => key($options),
      '#empty' => $this->t('No items found.'),
      '#multiple' => FALSE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#button_type' => 'primary',
      '#submit' => [
        $this::confirmSubmissionHandlerFileMerge(...),
      ],
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $form;
  }

  /**
   * Confirm form submission.
   */
  public function confirmSubmissionHandlerFileMerge(array &$form, FormStateInterface $form_state): void {
    /** @var array{confirm: true|null, files_being_merged: int[], all_references: array<string, \Drupal\auditfiles\Reference\FileEntityReference>, stage: string} $storage */
    $storage = $form_state->getStorage();

    // Flatten file ID -> file reference object.
    /** @var array<int, \Drupal\auditfiles\Reference\FileEntityReference> $files */
    $files = [];
    array_walk_recursive($storage['all_references'], function (FileEntityReference $reference) use (&$files): void {
      $files[$reference->getId()] = $reference;
    });

    $fileIdToKeep = (int) $form_state->getValue('file_being_kept');
    /** @var int[] $fileIdsToMerge */
    $fileIdsToMerge = array_diff($storage['files_being_merged'], [$fileIdToKeep]);

    $batch = (new BatchBuilder())
      ->setTitle('Merging files')
      ->setErrorMessage(\t('One or more errors were encountered processing the files.'))
      ->setFinishCallback([AuditFilesMergeFileReferencesBatchProcess::class, 'finishBatch'])
      ->setProgressMessage('Completed @current of @total operations.');
    foreach ($fileIdsToMerge as $fileId) {
      $batch->addOperation([AuditFilesMergeFileReferencesBatchProcess::class, 'create'], [
        $files[$fileIdToKeep] ?? throw new \LogicException('File ID to keep reference went missing'),
        $files[$fileId] ?? throw new \LogicException('File ID to merge reference went missing'),
      ]);
    }

    $this->batchSet($batch);
  }

  /**
   * Sets the batch.
   */
  protected function batchSet(BatchBuilder $batchDefinition): void {
    \batch_set($batchDefinition->toArray());
  }

}
