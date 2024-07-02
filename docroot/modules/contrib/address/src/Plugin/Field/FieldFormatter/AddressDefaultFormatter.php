<?php

namespace Drupal\address\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Locale;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\address\AddressInterface;
use Drupal\address\FieldHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'address_default' formatter.
 *
 * @FieldFormatter(
 *   id = "address_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * Site default country
   */
  const SITE_DEFAULT = 'site_default';

  /**
   * The address format repository.
   *
   * @var \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an AddressDefaultFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface $address_format_repository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AddressFormatRepositoryInterface $address_format_repository, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository, ConfigFactoryInterface $config_factory = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->addressFormatRepository = $address_format_repository;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;

    if (!$config_factory instanceof ConfigFactoryInterface) {
      @trigger_error('AddressDefaultFormatter now takes an additional argument ConfigFactoryInterface $config_factory. Use without $config_factory is deprecated. Classes extending this formatter will fail starting with address 9.x-1.x.', E_USER_DEPRECATED);
      $this->configFactory = \Drupal::configFactory();
    }
    else {
      $this->configFactory = $config_factory;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'skip_domestic_country' => FALSE,
      'domestic_country' => self::SITE_DEFAULT,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $country_list = $this->countryRepository->getList();
    $site_default = $this->t('- Site default (@country) -', [
      '@country' => $country_list[$this->configFactory->get('system.date')->get('country.default')]
    ]);

    $element = [];
    $element['skip_domestic_country'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip country for domestic addresses'),
      '#default_value' => $this->getSetting('skip_domestic_country'),
    ];

    $states_prefix = 'fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings]';
    $element['domestic_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Domestic addresses country'),
      '#options' => [self::SITE_DEFAULT => $site_default] + $country_list,
      '#default_value' => $this->getSetting('domestic_country'),
      '#empty_value' => NULL,
      '#description' => $this->t("Addresses within this country are considered domestic."),
      '#states' => [
        'visible' => [
          ':input[name="' . $states_prefix . '[skip_domestic_country]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if (!empty($this->getSetting('skip_domestic_country'))) {
      $domestic_country = $this->getSetting('domestic_country');
      $country_list = $this->countryRepository->getList();
      if ($domestic_country === self::SITE_DEFAULT) {
        $domestic_country = $this->t('Site default (@country)', [
          '@country' => $country_list[$this->configFactory->get('system.date')->get('country.default')]
        ]);
      }
      else {
        $domestic_country = $country_list[$domestic_country];
      }
      $summary['domestic_country'] = $this->t('Skip country for addresses in: @country', ['@country' => $domestic_country]);
    }
    else {
      $summary['domestic_country'] = $this->t('Always display country.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#prefix' => '<p class="address" translate="no">',
        '#suffix' => '</p>',
        '#post_render' => [
          [get_class($this), 'postRender'],
        ],
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
          ],
        ],
      ];
      $elements[$delta] += $this->viewElement($item, $langcode);
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressInterface $address, $langcode) {
    $country_code = $address->getCountryCode();
    $address_format = $this->addressFormatRepository->get($country_code);
    $values = $this->getValues($address, $address_format);

    $element = [
      '#address_format' => $address_format,
      '#locale' => $address->getLocale(),
    ];
    if ($this->getSetting('skip_domestic_country')) {
      $domestic_country = $this->getSetting('domestic_country');
      if ($domestic_country == self::SITE_DEFAULT) {
        $domestic_country = $this->configFactory->get('system.date')->get('country.default');
      }
    }
    if (!isset($domestic_country) || $country_code != $domestic_country) {
      $countries = $this->countryRepository->getList();
      $element['country'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => ['country']],
        '#value' => Html::escape($countries[$country_code]),
        '#placeholder' => '%country',
      ];
    }
    foreach ($address_format->getUsedFields() as $field) {
      $property = FieldHelper::getPropertyName($field);
      $class = str_replace('_', '-', $property);

      $element[$property] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => [$class]],
        '#value' => !empty($values[$field]) ? Html::escape($values[$field]) : '',
        '#placeholder' => '%' . $field,
      ];
    }

    return $element;
  }

  /**
   * Inserts the rendered elements into the format string.
   *
   * @param string $content
   *   The rendered element.
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return string
   *   The new rendered element.
   */
  public static function postRender($content, array $element) {
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format */
    $address_format = $element['#address_format'];
    $locale = $element['#locale'];

    $format_string = $address_format->getFormat();
    if (!empty($element['country'])) {
      // Add the country to the bottom or the top of the format string,
      // depending on whether the format is minor-to-major or major-to-minor.
      if (Locale::matchCandidates($address_format->getLocale(), $locale)) {
        $format_string = '%country' . "\n" . $address_format->getLocalFormat();
      }
      else {
        $format_string = $address_format->getFormat() . "\n" . '%country';
      }
    }

    $replacements = [];
    foreach (Element::getVisibleChildren($element) as $key) {
      $child = $element[$key];
      if (isset($child['#placeholder'])) {
        $replacements[$child['#placeholder']] = $child['#value'] ? $child['#markup'] : '';
      }
    }
    $content = self::replacePlaceholders($format_string, $replacements);
    $content = nl2br($content, FALSE);

    return $content;
  }

  /**
   * Replaces placeholders in the given string.
   *
   * @param string $string
   *   The string containing the placeholders.
   * @param array $replacements
   *   An array of replacements keyed by their placeholders.
   *
   * @return string
   *   The processed string.
   */
  public static function replacePlaceholders($string, array $replacements) {
    // Make sure the replacements don't have any unneeded newlines.
    $replacements = array_map('trim', $replacements);
    $string = strtr($string, $replacements);
    // Remove noise caused by empty placeholders.
    $lines = explode("\n", $string);
    foreach ($lines as $index => $line) {
      // Remove leading punctuation, excess whitespace.
      $line = trim(preg_replace('/^[-,]+/', '', $line, 1));
      $line = preg_replace('/\s\s+/', ' ', $line);
      $lines[$index] = $line;
    }
    // Remove empty lines.
    $lines = array_filter($lines);

    return implode("\n", $lines);
  }

  /**
   * Gets the address values used for rendering.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format
   *   The address format.
   *
   * @return array
   *   The values, keyed by address field.
   */
  protected function getValues(AddressInterface $address, AddressFormat $address_format) {
    $values = [];
    foreach (AddressField::getAll() as $field) {
      $getter = 'get' . ucfirst($field);
      $values[$field] = $address->$getter();
    }

    $original_values = [];
    $subdivision_fields = $address_format->getUsedSubdivisionFields();
    $parents = [];
    foreach ($subdivision_fields as $index => $field) {
      if (empty($values[$field])) {
        // This level is empty, so there can be no sublevels.
        break;
      }
      $parents[] = $index ? $original_values[$subdivision_fields[$index - 1]] : $address->getCountryCode();
      $subdivision = $this->subdivisionRepository->get($values[$field], $parents);
      if (!$subdivision) {
        break;
      }

      // Remember the original value so that it can be used for $parents.
      $original_values[$field] = $values[$field];
      // Replace the value with the expected code.
      $use_local_name = Locale::matchCandidates($address->getLocale(), $subdivision->getLocale());
      $values[$field] = $use_local_name ? $subdivision->getLocalCode() : $subdivision->getCode();
      if (!$subdivision->hasChildren()) {
        // The current subdivision has no children, stop.
        break;
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['postRender'];
  }

}
