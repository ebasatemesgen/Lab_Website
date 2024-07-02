<?php

namespace Drupal\field_redirection\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_redirection_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_redirection_formatter",
 *   label = @Translation("Redirect"),
 *   field_types = {
 *     "entity_reference",
 *     "file",
 *     "link"
 *   }
 * )
 */
class FieldRedirectionFormatter extends FormatterBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The redirect result builder.
   *
   * @var \Drupal\field_redirection\FieldRedirectionResultBuilder
   */
  protected $redirectResultBuilder;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The standard HTTP redirection codes that are supported.
   *
   * @var array
   */
  protected $httpCodes = [
    '300' => '300: Multiple Choices (rarely used)',
    '301' => '301: Moved Permanently (default)',
    '302' => '302: Found (rarely used)',
    '303' => '303: See Other (rarely used)',
    '304' => '304: Not Modified (rarely used)',
    '305' => '305: Use Proxy (rarely used)',
    '307' => '307: Temporary Redirect (temporarily moved)',
  ];

  /**
   * Restrictions that may be applied to this redirection.
   *
   * @var array
   */
  protected $pageRestrictionOptions = [
    '0' => 'Redirect on all pages.',
    '1' => 'Redirect only on the following pages.',
    '2' => 'Redirect on all pages except the following pages.',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    $instance->redirectResultBuilder = $container->get('field_redirection.result_builder');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'code' => '301',
      '404_if_empty' => FALSE,
      'page_restrictions' => 0,
      'pages' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Load the current selection, default to "301".
    $code = 301;
    if (!empty($this->getSetting('code')) && isset($this->httpCodes[$this->getSetting('code')])) {
      $code = $this->getSetting('code');
    }
    // Choose the redirector.
    $elements['code'] = [
      '#title' => 'HTTP status code',
      '#type' => 'select',
      '#options' => [],
      '#default_value' => $code,
    ];
    foreach ($this->httpCodes as $code => $label) {
      $elements['code']['#options'][$code] = $this->t('@label', ['@label' => $label]);
    }

    // 404 if the field value is empty.
    $elements['404_if_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('404 if URL empty'),
      '#default_value' => !empty($this->getSetting('404_if_empty')),
      '#description' => $this->t('Optionally display a 404 error page if the associated URL field is empty.'),
    ];

    $elements['note'] = [
      '#markup' => $this->t('Note: If the destination path is the same as the current path it will behave as if it is empty.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Provide targeted URL rules to trigger this action.
    $elements['page_restrictions'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect page restrictions'),
      '#default_value' => empty($this->getSetting('page_restrictions')) ? 0 : $this->getSetting('page_restrictions'),
      '#options' => [],
    ];
    foreach ($this->pageRestrictionOptions as $code => $label) {
      $elements['page_restrictions']['#options'][$code] = $this->t('@label', ['@label' => $label]);
    }

    $elements['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#default_value' => empty($this->getSetting('pages')) ? '' : $this->getSetting('pages'),
      '#description' => $this->t("Enter one page per line as Drupal paths. The '@wildcard' character is a wildcard. Example paths are '@example_blog' for the blog page and '@example_all_personal_blogs' for every personal blog. '@frontpage' is the front page. You can also use tokens in this field, for example '@example_current_node' can be used to define the current node path.", [
        '@wildcard' => '*',
        '@example_blog' => 'blog',
        '@example_all_personal_blogs' => 'blog/*',
        '@frontpage' => '<front>',
        '@example_current_node' => 'node/[node:nid]',
      ]),
      '#states' => [
        'invisible' => [
          ':input[name*="[page_restrictions]"]' => ['value' => '0'],
        ],
      ],
    ];

    $elements['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => 'all',
      '#weight' => 100,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    // Display a "hair on fire" warning message for any view mode other than
    // "full".
    if ($this->viewMode != 'full') {
      $this->messenger()->addWarning($this->t('Danger! The Redirect formatter should not be used with any view mode other than "Full content".'));
    }

    if (!empty($settings['code'])) {
      $summary[] = $this->t('HTTP status code: @code', ['@code' => $this->httpCodes[$settings['code']]]);
    }

    if ($settings['404_if_empty']) {
      $summary[] = $this->t('Will return 404 (page not found) if field is empty.');
    }

    if (!empty($settings['page_restrictions'])) {
      $page_restrictions = $this->pageRestrictionOptions;
      $summary[] = $this->t('Page restriction options: @pagerestriction', ['@pagerestriction' => $page_restrictions[$settings['page_restrictions']]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Don't do anything if running via the CLI, e.g. Drush.
    // Do this here instead of in the builder so tests don't exit early.
    if (php_sapi_name() == 'cli') {
      return $elements;
    }

    $settings = $this->getSettings();

    // Set response code.
    $response_code = 301;
    if (!empty($settings['code']) && isset($this->httpCodes[$settings['code']])) {
      $response_code = $settings['code'];
    }

    /** @var \Drupal\field_redirection\FieldRedirectionResult $result */
    $result = $this->redirectResultBuilder->buildResult($items, $this->request, $this->currentUser, $settings);
    if ($result->shouldRedirect()) {
      $result->asRedirectResponse($response_code)->send();
      exit;
    }

    // If the user has permission to bypass the page redirection, return a
    // message explaining where they would have been redirected to.
    if ($this->currentUser->hasPermission('bypass redirection') && !$items->isEmpty()) {
      $url = $this->redirectResultBuilder->getUrl($items);
      $message = $this->t(
        'This page is set to redirect to <a href="@href">another URL</a>, but you have permission to see this page and will not be automatically redirected.', [
          '@href' => $url->toString(),
        ]
      );
      $this->messenger()->addWarning($message);
    }

    return $elements;
  }


}
