<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Form;

use Drupal\auditfiles\Auditor\AuditFilesUsedNotReferenced;
use Drupal\auditfiles\Batch\AuditFilesDeleteFileUsageBatchProcess;
use Drupal\auditfiles\Reference\FileEntityReference;
use Drupal\auditfiles\Reference\FileUsageReference;
use Drupal\auditfiles\Services\AuditFilesConfigInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
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
 * Used not referenced.
 *
 * List files in the file_usage table, but not referenced in content.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesUsedNotReferencedForm extends FormBase implements AuditFilesAuditorFormInterface {

  use AuditFilesAuditorFormTrait;

  protected const TEMPORARY_ALL_REFERENCES = 'references';

  /**
   * Constructs a new AuditFilesUsedNotReferencedForm.
   */
  final public function __construct(
    protected AuditFilesConfigInterface $auditFilesConfig,
    protected AuditFilesUsedNotReferenced $filesUsedNotReferenced,
    protected PagerManagerInterface $pagerManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected Connection $connection,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('auditfiles.config'),
      $container->get('auditfiles.auditor.used_not_referenced'),
      $container->get('pager.manager'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'audit_files_used_not_referenced';
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
    return 'audit_files_used_not_referenced';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('auditfiles.reports.usednotreferenced');
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
    /** @var \Drupal\auditfiles\Reference\FileEntityReference[] $references */
    $references = iterator_to_array($this->filesUsedNotReferenced->getReferences());
    $form_state->setTemporaryValue(static::TEMPORARY_ALL_REFERENCES, $references);

    $rows = \array_reduce(
      $references,
      function (?array $rows, FileEntityReference $reference): array {
        $file = $reference->getFile();
        $fileId = $reference->getId();
        if ($file === NULL) {
          $rows[$reference->getId()] = [
            'fid' => [
              'colspan' => 3,
              'data' => $this->t('This file is not listed in the file_managed table. See the "%usednotmanaged" report.', [
                '%usednotmanaged' => Link::fromTextAndUrl(
                  $this->t('Used not managed'),
                  Url::fromRoute('auditfiles.reports.usednotmanaged'),
                )->toString(),
              ]),
            ],
          ];
          return $rows;
        }

        $usages = [];
        foreach ($this->getFileUsageForFile($reference) as $fileUsageReference) {
          $source = NULL;
          try {
            $source = $this->entityTypeManager
              ->getStorage($fileUsageReference->getEntityTypeId())
              ->load($fileUsageReference->getEntityId());
          }
          catch (PluginNotFoundException) {
          }
          $sourceLink = ($source ?? NULL)?->toLink(text: $source->id())->toString() ?? $fileUsageReference->getEntityId();
          $usages[] = $this->t('Used by module: %used_by, as object type: %type, in content ID: %used_in; Times used: %times_used', [
            '%used_by' => $fileUsageReference->getModule(),
            '%type' => $fileUsageReference->getEntityTypeId(),
            '%used_in' => $sourceLink,
            '%times_used' => $fileUsageReference->getCount(),
          ]);
        }

        $rows[$reference->getId()] = [
          'fid' => $fileId,
          'uri' => Link::fromTextAndUrl(
            $file->getFileUri(),
            Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()), [
              'attributes' => [
                'target' => '_blank',
              ],
            ]),
          ),
          'usage' => [
            'data' => [
              '#theme' => 'item_list',
              '#tree' => TRUE,
              '#items' => $usages,
            ],
          ],
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
      'Found 1 file in the file_usage table that is not referenced in content.',
      (($maximumRecords !== 0) ? 'Found at least @count files in the file_usage table not referenced in content.' : 'Found @count files in the file_usage table not referenced in content.'),
    ) : $this->t('Found no files in the file_usage table not referenced in content.');

    // Create the form table.
    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => [
        'fid' => [
          'data' => $this->t('File ID'),
        ],
        'uri' => [
          'data' => $this->t('File URI'),
        ],
        'usage' => [
          'data' => $this->t('Usages'),
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
    $form['#title'] = $this->t('Delete these items from the file_usage table?');

    /** @var array{confirm: true|null, references: \Drupal\auditfiles\Reference\FileEntityReference[]} $storage */
    $storage = &$form_state->getStorage();

    $form['changelist'] = [
      '#theme' => 'item_list',
      '#tree' => TRUE,
      '#items' => [],
    ];

    foreach ($storage['references'] as $reference) {
      $tArgs = ['@file' => $reference->getFile()?->getFilename() ?? '- Missing file -'];
      $form['changelist']['#items'][] = $this->t('<strong>@file</strong> will be deleted from the file_usage table.', $tArgs);
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
   * Submit form after confirmation.
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
   * Get file usage for a file.
   *
   * @param \Drupal\auditfiles\Reference\FileEntityReference $reference
   *   A file entity reference.
   *
   * @return \Generator<\Drupal\auditfiles\Reference\FileUsageReference>
   *   File usage references.
   */
  private function getFileUsageForFile(FileEntityReference $reference): \Generator {
    $result = $this->connection->select('file_usage', 'fu')
      ->fields('fu', ['fid', 'module', 'type', 'id', 'count'])
      ->condition('fu.fid', $reference->getId())
      ->execute();
    while ($row = $result->fetch()) {
      yield FileUsageReference::createFromRow($row);
    }
  }

  /**
   * Sets the batch.
   */
  protected function batchSet(BatchBuilder $batchDefinition): void {
    \batch_set($batchDefinition->toArray());
  }

}
