<?php

namespace Drupal\Tests\contact_emails\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Base class for contact emails tests.
 *
 * @group contact_emails
 */
trait ContactEmailsTestBaseTrait {

  /**
   * The admin user.
   *
   * @var bool|\Drupal\user\UserInterface
   */
  protected $adminUser = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->createUserAndLogin();
    $this->createBaseContactForm();
  }

  /**
   * Creates the admin user and logs in.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createUserAndLogin(): void {
    // Create the user.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a base contact form for use in all tests.
   */
  protected function createBaseContactForm(): void {
    // Create a contact form.
    $params = [
      'label' => 'Contact Emails Test Form',
      'id' => 'contact_emails_test_form',
      'message' => 'Your message has been sent.',
      'recipients' => 'test@example.com',
      'contact_storage_submit_text' => 'Send message',
    ];
    $this->drupalGet('/admin/structure/contact/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Save');
  }

  /**
   * Set the site email.
   */
  protected function setSiteMail(): void {
    $settings['config']['system.site']['mail'] = (object) [
      'value' => 'site-default-mail@test.com',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Helper function to add an email field to the contact form.
   */
  protected function addEmailFieldToContactForm(): void {
    // Add the field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_email_address',
      'entity_type' => 'contact_message',
      'type' => 'email',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_email_address',
      'entity_type' => 'contact_message',
      'bundle' => 'contact_emails_test_form',
      'label' => 'Email address',
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepository $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('contact_message', 'contact_emails_test_form', 'default')
      ->setComponent('field_email_address', [
        'type' => 'email_default',
        'region' => 'content',
      ])
      ->save();
  }

  /**
   * Helper function to create additional contact form to test referencing.
   */
  protected function addContactFormWithEmailFieldForReferencing(): void {
    // Create a contact form.
    $params = [
      'label' => 'Contact Reference Test Form',
      'id' => 'contact_reference_test_form',
      'message' => 'Your message has been sent.',
      'recipients' => 'test@example.com',
      'contact_storage_submit_text' => 'Send message',
    ];
    $this->drupalGet('/admin/structure/contact/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Save');

    /** @var \Drupal\Core\Entity\EntityDisplayRepository $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Add an email field to be referenced.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_email_reference',
      'entity_type' => 'contact_message',
      'type' => 'email',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_email_reference',
      'entity_type' => 'contact_message',
      'bundle' => 'contact_reference_test_form',
      'label' => 'Email address',
    ]);
    $field->save();

    $display_repository->getFormDisplay('contact_message', 'contact_reference_test_form', 'default')
      ->setComponent('field_email_reference', [
        'type' => 'email_default',
        'region' => 'content',
      ])
      ->save();

    // Add an email field to reference the new form's field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'contact_message',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'contact_message',
      ],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'contact_message',
      'bundle' => 'contact_emails_test_form',
      'label' => 'Reference',
      'settings' => [
        'handler' => 'default:contact_message',
        'handler_settings' => [
          'target_bundles' => [
            'contact_reference_test_form' => 'contact_reference_test_form',
          ],
        ],
      ],
    ]);
    $field->save();

    $display_repository->getFormDisplay('contact_message', 'contact_emails_test_form', 'default')
      ->setComponent('field_reference', [
        'type' => 'options_select',
        'region' => 'content',
      ])
      ->save();

    drupal_flush_all_caches();

    // Submit the refernce contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
      'field_email_reference[0][value]' => 'email-via-reference@test.com',
    ];
    $this->drupalGet('/contact/contact_reference_test_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Send message');

    // Assert that it says message has been sent.
    $this->assertSession()->pageTextContains('Your message has been sent.');
  }

}
