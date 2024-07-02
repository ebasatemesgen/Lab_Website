<?php

namespace Drupal\Tests\contact_emails\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests contact emails reply to and recipients.
 *
 * @group contact_emails
 */
class ContactEmailsReplyToTest extends BrowserTestBase {

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
  public function testReplyToDefault(): void {
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

    // Assert that the reply-to is the default site email.
    $this->assertSession()->pageTextContains('Message-reply-to:site-default-mail@test.com');
  }

  /**
   * Test field functionality of reply-to email address.
   *
   * @throws \Exception
   */
  public function testReplyToField(): void {
    $this->addEmailFieldToContactForm();

    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'field',
      'reply_to_field[0][value]' => 'field_email_address',
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

    // Assert that it says message has been sent.
    $this->assertSession()->pageTextContains('Message-reply-to:email.in.field@test.com');
  }

  /**
   * Test form submitter functionality reply-to email address.
   *
   * @throws \Exception
   */
  public function testReplyToFormSubmitter(): void {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'submitter',
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
    $this->assertSession()->pageTextContains('Message-reply-to:' . \Drupal::currentUser()->getEmail());
  }

  /**
   * Test manual functionality reply-to email address.
   *
   * @throws \Exception
   */
  public function testReplyToManual(): void {
    // Add the email.
    $params = [
      'subject[0][value]' => 'Contact Emails Test Form Subject',
      'message[0][value]' => 'Contact Emails Test Form Body',
      'recipient_type[0][value]' => 'default',
      'reply_to_type[0][value]' => 'manual',
      'reply_to_email[0][value]' => 'manual-email-1@test.com',
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
    $this->assertSession()->pageTextContains('Message-reply-to:manual-email-1@test.com');
  }

}
