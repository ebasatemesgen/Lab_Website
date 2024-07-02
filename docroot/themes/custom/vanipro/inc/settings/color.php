<?php
/**
 * Color Settings
 */
$form['colord']['color_info'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Color Scheme Settings'),
    '#description'   => t('These settings adjust the look and feel of the ZuviPro theme. Changing the color below will change the color of ZuviPro theme.'),
  ];
  $form['colord']['color_scheme_option'] = [
    '#type' => 'fieldset',
    '#title' => t('Color Scheme'),
  ];
  $form['colord']['color_scheme_option']['color_scheme'] = [
    '#type'          => 'select',
    '#title' => t('Select Color Scheme'),
    '#options' => array(
      'color_default' => t('Default'),
      'color_custom' => t('Custom'),
      ),
    '#default_value' => theme_get_setting('color_scheme'),
    '#description'   => t('Default will set the theme to default color scheme. Custom will set the theme color as set below.')
  ];
  $form['colord']['color_custom'] = [
    '#type' => 'fieldset',
    '#title' => t('Custom Color Scheme'),
    '#description'   => t('Customize color of the theme. This will work if you have selected <strong>Custom</strong> color scheme above.')
  ];
  $form['colord']['color_custom']['color_primary'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_primary'),
    '#title'       => t('Primary Color'),
    '#default_value' => theme_get_setting('color_primary'),
    '#description' => t('<p>Default value is <strong>#ff6347</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_secondary'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_secondary'),
    '#title'       => t('Secondary Color'),
    '#default_value' => theme_get_setting('color_secondary'),
    '#description' => t('<p>Default value is <strong>#302e44</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['bg_body'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('bg_body'),
    '#title'       => t('Body Background'),
    '#default_value' => theme_get_setting('bg_body'),
    '#description' => t('<p>Default value is <strong>#ffffff</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['block_bg'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('block_bg'),
    '#title'       => t('Header and Sidebar Background'),
    '#default_value' => theme_get_setting('block_bg'),
    '#description' => t('<p>Default value is <strong>#f2f4ff</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['bg_header_top'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('bg_header_top'),
    '#title'       => t('Header Top Background'),
    '#default_value' => theme_get_setting('bg_header_top'),
    '#description' => t('<p>Default value is <strong>#f2f4fe</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['bg_footer'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('bg_footer'),
    '#title'       => t('Footer Background'),
    '#default_value' => theme_get_setting('bg_footer'),
    '#description' => t('<p>Default value is <strong>#232733</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_footer'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_footer'),
    '#title'       => t('Footer Color'),
    '#default_value' => theme_get_setting('color_footer'),
    '#description' => t('<p>Default value is <strong>#919296</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_footer_line'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_footer_line'),
    '#title'       => t('Footer Light Color'),
    '#default_value' => theme_get_setting('color_footer_line'),
    '#description' => t('<p>Default value is <strong>#373b48</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_light'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_light'),
    '#title'       => t('Light Color'),
    '#default_value' => theme_get_setting('color_light'),
    '#description' => t('<p>Default value is <strong>#e7a498</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_border'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_border'),
    '#title'       => t('Line and Border Color'),
    '#default_value' => theme_get_setting('color_border'),
    '#description' => t('<p>Default value is <strong>#e9ebf5</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_text'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_text'),
    '#title'       => t('Text Color'),
    '#default_value' => theme_get_setting('color_text'),
    '#description' => t('<p>Default value is <strong>#2a2a2a</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_heading'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_heading'),
    '#title'       => t('Heading Color'),
    '#default_value' => theme_get_setting('color_heading'),
    '#description' => t('<p>Default value is <strong>#1e2127</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_circle_one'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_circle_one'),
    '#title'       => t('Circle One Color'),
    '#default_value' => theme_get_setting('color_circle_one'),
    '#description' => t('<p>Default value is <strong>#f3e7f0</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_circle_two'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_circle_two'),
    '#title'       => t('Circle Two Color'),
    '#default_value' => theme_get_setting('color_circle_two'),
    '#description' => t('<p>Default value is <strong>#e0e2f6</strong></p><p><hr /></p>'),
  ];
  $form['colord']['color_custom']['color_circle_three'] = [
    '#type'        => 'color',
    '#field_suffix' => theme_get_setting('color_circle_three'),
    '#title'       => t('Circle Three Color'),
    '#default_value' => theme_get_setting('color_circle_three'),
    '#description' => t('<p>Default value is <strong>#f9b6b4</strong></p><p><hr /></p>'),
  ];