<?php

namespace Drupal\contact_emails\Routing;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines dynamic routes.
 */
class RouteCallback {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function addFormTitle($contact_form) {
    return $this->t('Add New Contact Email to "@contact_form"', [
      '@contact_form' => $contact_form->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function editFormTitle($contact_email) {
    $contact_form_id = $contact_email->get('contact_form')->target_id;
    $contact_form = ContactForm::load($contact_form_id);
    return $this->t('Edit Contact Email @id for "@contact_form"', [
      '@id' => $contact_email->id(),
      '@contact_form' => $contact_form->label(),
    ]);
  }

}
