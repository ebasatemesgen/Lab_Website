<?php

namespace Drupal\Tests\contact_emails\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests contact emails sending functionality.
 *
 * @group contact_emails
 */
class ContactEmailsDefaultEmailTest extends BrowserTestBase {

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
  ];

  /**
   * Test default functionality of sending an email.
   *
   * @throws \Exception
   */
  public function testSendEmail(): void {
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

    // Assert that it says message has been sent.
    $this->assertSession()->pageTextContains('Your message has been sent.');

    // Assert subject and body.
    $this->assertSession()->pageTextContains('Contact Emails Test Form Subject');
    $this->assertSession()->pageTextContains('Contact Emails Test Form Body');
  }

}
