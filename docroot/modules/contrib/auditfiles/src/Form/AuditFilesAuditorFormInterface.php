<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Form;

use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for auditor forms.
 *
 * Each is a two-step confirmation.
 */
interface AuditFilesAuditorFormInterface extends ConfirmFormInterface {

  /**
   * Build the list form.
   */
  public function buildListForm(array $form, FormStateInterface $form_state): array;

  /**
   * Build the confirm form.
   */
  public function buildConfirmForm(array $form, FormStateInterface $form_state): array;

}
