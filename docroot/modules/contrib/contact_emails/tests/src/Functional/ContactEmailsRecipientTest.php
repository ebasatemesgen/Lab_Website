<?php

namespace Drupal\Tests\contact_emails\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests contact emails recipients.
 *
 * @group contact_emails
 */
class ContactEmailsRecipientTest extends BrowserTestBase {

  use ContactEmailsTestBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contact',
    'contact_storage',
    'contact_emails',
    'contact_emails_test_mail_alter',
    'field_ui',
  ];

  /**
   * Test default functionality to email address.
   */
  public function testSendToDefault(): void {
    $this->setSiteMail();

    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'default',
      'status[value]' => TRUE,
    ];
    $this->drupalGet('/admin/structure/contact/manage/contact_emails_test_form/emails/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Save');

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
    ];
    $this->drupalGet('/contact/contact_emails_test_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Send message');

    // Assert that the to is the default site email.
    $this->assertSession()->pageTextContains('Message-to:site-default-mail@test.com');
  }

  /**
   * Test field functionality to email address.
   */
  public function testSendToField(): void {
    $this->addEmailFieldToContactForm();

    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'field',
      'recipient_field[0][value]' => 'field_email_address',
      'reply_to_type[0][value]' => 'default',
      'status[value]' => TRUE,
    ];
    $this->drupalGet('/admin/structure/contact/manage/contact_emails_test_form/emails/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Save');

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
      'field_email_address[0][value]' => 'email.in.field@test.com',
    ];
    $this->drupalGet('/contact/contact_emails_test_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Send message');

    // Assert that the message to is the value of the field.
    $this->assertSession()->pageTextContains('Message-to:email.in.field@test.com');
  }

  /**
   * Test form submitter functionality to email address.
   */
  public function testSendToFormSubmitter(): void {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'submitter',
      'reply_to_type[0][value]' => 'default',
      'status[value]' => TRUE,
    ];
    $this->drupalGet('/admin/structure/contact/manage/contact_emails_test_form/emails/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Save');

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
    ];
    $this->drupalGet('/contact/contact_emails_test_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Send message');

    // Assert that the message to is the email of the currently logged in user.
    $this->assertSession()->pageTextContains('Message-to:' . \Drupal::currentUser()->getEmail());
  }

  /**
   * Test manual functionality to email address.
   */
  public function testSendToManual(): void {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'manual',
      'recipients[0][value]' => 'manual-email-1@test.com, manual-email-2@test.com',
      'reply_to_type[0][value]' => 'default',
      'status[value]' => TRUE,
    ];
    $this->drupalGet('/admin/structure/contact/manage/contact_emails_test_form/emails/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Save');

    // Submit the contact form on the front-end of the website.
    $params = [
      'subject[0][value]' => 'Submission Test Form Subject',
      'message[0][value]' => 'Submission Test Form Body',
    ];
    $this->drupalGet('/contact/contact_emails_test_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($params, 'Send message');

    // Assert that the message to is the email of the currently logged in user.
    $this->assertSession()->pageTextContains('Message-to:manual-email-1@test.com, manual-email-2@test.com');
  }

}
