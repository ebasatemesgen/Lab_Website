<?php

namespace Drupal\contact_emails;

use Drupal\contact_emails\Entity\ContactEmailInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for tax services.
 */
class ContactEmailListBuilder extends EntityListBuilder {

  /**
   * Drupal\contact_emails\ContactEmails definition.
   *
   * @var \Drupal\contact_emails\ContactEmails
   */
  protected $contactEmails;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ContactEmailListBuilder class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ContactEmails $contact_emails, RouteMatchInterface $route_match) {
    parent::__construct($entity_type, $storage);

    $this->contactEmails = $contact_emails;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('contact_emails.helper'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['contact_form'] = $this->t('Contact form');
    $header['subject'] = $this->t('Subject');
    $header['recipients'] = $this->t('Recipients');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\contact_emails\Entity\ContactEmailInterface $entity */
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $entity->get('contact_form')->entity;
    $type = $entity->get('recipient_type')->getString();

    $row['id'] = $entity->id();
    $row['contact_form'] = $contact_form ? $contact_form->label() : '';
    $row['subject'] = $entity->label();
    $row['recipients'] = 'context' === $type ? '@context_user' : $this->getRecipients($entity);
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // If this is a list for a particular contact form, set a useful empty
    // message.
    if ($contact_form = $this->routeMatch->getParameter('contact_form')) {
      $build['table']['#empty'] = $this->t('The default contact emails are being used. <a href=":url_edit">Modify the default emails here</a> or <a href=":url_create">override them with new contact emails</a>.', [
        ':url_edit' => Url::fromRoute('entity.contact_form.edit_form', ['contact_form' => $contact_form])->toString(),
        ':url_create' => Url::fromRoute('entity.contact_email.add_form', ['contact_form' => $contact_form])->toString(),
      ]);
    }
    return $build;
  }

  /**
   * Gets the recipient text to display.
   *
   * @param \Drupal\contact_emails\Entity\ContactEmailInterface $entity
   *   The contact email entity.
   *
   * @return string
   *   The recipients text.
   */
  protected function getRecipients(ContactEmailInterface $entity) {
    switch ($entity->get('recipient_type')->value) {
      case 'default':
        $value = $this->t('[The site email address]');
        break;

      case 'submitter':
        $value = $this->t('[The submitter of the form]');
        break;

      case 'field':
        $value = $this->recipientFieldValue($entity, 'recipient_field', 'email');
        break;

      case 'reference':
        $value = $this->recipientFieldValue($entity, 'recipient_reference', 'entity_reference');
        break;

      case 'manual':
      default:
        $recipients = [];
        foreach ($entity->get('recipients')->getValue() as $value) {
          $recipients[] = $value['value'];
        }

        $value = implode(', ', $recipients);
        break;
    }

    return $value;
  }

  /**
   * Get the description of recipient field value.
   *
   * @param \Drupal\contact_emails\Entity\ContactEmailInterface $entity
   *   The email.
   * @param string $fieldName
   *   The field name.
   * @param string $fieldType
   *   The field type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description of the field.
   */
  protected function recipientFieldValue(ContactEmailInterface $entity, $fieldName, $fieldType) {
    $contact_form_id = $entity->get('contact_form')->target_id;
    $fields = $this->contactEmails->getContactFormFields($contact_form_id, $fieldType);

    $field_label = (
      $entity->hasField($fieldName)
      && !$entity->get($fieldName)->isEmpty()
      && isset($fields[$entity->get($fieldName)->value])
    )
      ? $entity->get($fieldName)->value
      : $this->t('*Unknown or deleted field*');

    return $this->t('[The value of the "@field" field]', [
      '@field' => $field_label,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->accessCheck();

    // Maybe filter by the selected contact form.
    if ($contact_form_id = $this->routeMatch->getParameter('contact_form')) {
      $query->condition('contact_form', $contact_form_id);
    }

    // Order by the id.
    $query->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
