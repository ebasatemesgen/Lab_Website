<?php
/**
 * @file
 * Custom setting for VaniPro theme.
 */
use Drupal\Core\Form\FormStateInterface;
function vanipro_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  $form['#attached']['library'][] = 'vanipro/theme-settings';
  $ver = '10.1.1';
  $theme_update_info = file_get_contents("https://drupar.com/theme-update-info/vanipro.txt");
  $form['vanipro'] = [
    '#type'       => 'vertical_tabs',
    '#title'      => '<h3>' . t('VaniPro Theme Settings') . '</h3>',
    '#default_tab' => 'general',
  ];

  /**
   * Main Tabs.
   */

  // Main Tabs -> General.
  $form['general'] = [
    '#type'  => 'details',
    '#title' => t('General'),
    '#description' => t('<h3>Thank you for using VaniPro Theme</h3>VaniPro is a premium Drupal 8 and 9 theme designed and developed by <a href="https://drupar.com" target="_blank">Drupar.com</a>'),
    '#group' => 'vanipro',
  ];

  // Main Tabs -> color
  $form['colord'] = [
    '#type'  => 'details',
    '#title' => t('Color'),
    '#group' => 'vanipro',
  ];
  // Main Tabs -> Social.
  $form['social'] = [
    '#type'  => 'details',
    '#title' => t('Social'),
    '#description' => t('Social icons settings. These icons appear in the footer.'),
    '#group' => 'vanipro',
  ];
  // Main Tabs -> Homepage Slider.
  $form['slider'] = [
    '#type'  => 'details',
    '#title' => t('Homepage Slider'),
    '#group' => 'vanipro',
  ];

  // Main Tabs -> Header.
  $form['header'] = [
    '#type'  => 'details',
    '#title' => t('Header'),
    '#group' => 'vanipro',
  ];

  // Main Tabs -> Sidebar.
  $form['sidebar'] = [
    '#type'  => 'details',
    '#title' => t('Sidebar'),
    '#group' => 'vanipro',
  ];

  // Main Tabs -> Content.
  $form['content'] = [
    '#type'  => 'details',
    '#title' => t('Content'),
    '#group' => 'vanipro',
  ];

  // Main Tabs -> Footer.
  $form['footer'] = [
    '#type'  => 'details',
    '#title' => t('Footer'),
    '#group' => 'vanipro',
  ];
  // Main Tabs ->Insert codes
  $form['insert_codes'] = [
    '#type'  => 'details',
    '#title' => t('Insert Codes'),
    '#group' => 'vanipro',
  ];
  // Main Tabs -> Update.
  $form['update'] = [
    '#type'  => 'details',
    '#title' => t('Update'),
    '#description' => t('<h4>Check For Update</h4>'),
    '#group' => 'vanipro',
  ];
  
  // Main Tabs -> Licensing.
  $form['license'] = [
    '#type'  => 'details',
    '#title' => t('Theme License'),
    '#group' => 'vanipro',
  ];

  // Main Tabs -> Support.
  $form['support'] = [
    '#type'  => 'details',
    '#title' => t('Support'),
    '#description' => t('<h4>Support</h4><p>For any support related to VaniPro theme, please <a href="https://drupar.com/node/add/ticket" target="_blank">open a support ticket</a>.</p>'),
    '#group' => 'vanipro',
  ];

  // General -> info.
  $form['general']['theme_version'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Current Theme Version'),
    '#description' => t("$ver"),
  ];
  $form['general']['general_info'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Theme Info'),
    '#description' => t('<a href="https://drupar.com/theme/vanipro" target="_blank">Theme Homepage</a> || <a href="https://demo2.drupar.com/vanipro/" target="_blank">Theme Demo</a> || <a href="https://drupar.com/vanipro-documentation" target="_blank">Theme Documentation</a> || <a href="https://drupar.com/support" target="_blank">Theme Support</a>'),
  ];
  // Color tab
  include_once 'inc/settings/color.php';
  
  // Settings under social tab.
  $form['social']['social_profile'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Social Profile'),
  ];
  $form['social']['social_profile']['all_icons'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Show Social Icons'),
  ];

  $form['social']['social_profile']['all_icons']['all_icons_show'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show social icons in footer'),
    '#default_value' => theme_get_setting('all_icons_show', 'vanipro'),
    '#description'   => t("Check this option to show social icons in footer. Uncheck to hide."),
  ];

  // Facebook.
    $form['social']['social_profile']['facebook'] = [
    '#type'        => 'details',
    '#title'       => t("Facebook"),
  ];

  $form['social']['social_profile']['facebook']['facebook_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Facebook Url'),
    '#description'   => t("Enter yours facebook profile or page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('facebook_url', 'vanipro'),
  ];

  // Twitter.
  $form['social']['social_profile']['twitter'] = [
    '#type'        => 'details',
    '#title'       => t("Twitter"),
  ];

  $form['social']['social_profile']['twitter']['twitter_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Twitter Url'),
    '#description'   => t("Enter yours twitter page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('twitter_url', 'vanipro'),
  ];

  // Instagram.
  $form['social']['social_profile']['instagram'] = [
    '#type'        => 'details',
    '#title'       => t("Instagram"),
  ];

  $form['social']['social_profile']['instagram']['instagram_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Instagram Url'),
    '#description'   => t("Enter yours instagram page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('instagram_url', 'vanipro'),
  ];

  // Linkedin.
  $form['social']['social_profile']['linkedin'] = [
    '#type'        => 'details',
    '#title'       => t("Linkedin"),
  ];

  $form['social']['social_profile']['linkedin']['linkedin_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Linkedin Url'),
    '#description'   => t("Enter yours linkedin page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('linkedin_url', 'vanipro'),
  ];

  // YouTube.
  $form['social']['social_profile']['youtube'] = [
    '#type'        => 'details',
    '#title'       => t("YouTube"),
  ];

  $form['social']['social_profile']['youtube']['youtube_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('YouTube Url'),
    '#description'   => t("Enter yours youtube.com page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('youtube_url', 'vanipro'),
  ];

  // YouTube.
  $form['social']['social_profile']['vimeo'] = [
    '#type'        => 'details',
    '#title'       => t("vimeo"),
  ];

  $form['social']['social_profile']['vimeo']['vimeo_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('vimeo Url'),
    '#description'   => t("Enter yours vimeo.com page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('vimeo_url', 'vanipro'),
  ];

  // telegram.
    $form['social']['social_profile']['telegram'] = [
    '#type'        => 'details',
    '#title'       => t("Telegram"),
  ];

  $form['social']['social_profile']['telegram']['telegram_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Telegram Url'),
    '#description'   => t("Enter yours Telegram profile or page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('telegram_url', 'vanipro'),
  ];

  // WhatsApp.
    $form['social']['social_profile']['whatsapp'] = [
    '#type'        => 'details',
    '#title'       => t("WhatsApp"),
  ];

  $form['social']['social_profile']['whatsapp']['whatsapp_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('WhatsApp Url'),
    '#description'   => t("Enter yours whatsapp message url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('whatsapp_url', 'vanipro'),
  ];

  // Github.
    $form['social']['social_profile']['github'] = [
    '#type'        => 'details',
    '#title'       => t("GitHub"),
  ];

  $form['social']['social_profile']['github']['github_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('GitHub Url'),
    '#description'   => t("Enter yours github page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('github_url', 'vanipro'),
  ];

  // Social -> vk.com url.
  $form['social']['social_profile']['vk'] = [
    '#type'        => 'details',
    '#title'       => t("vk.com"),
  ];
  $form['social']['social_profile']['vk']['vk_url'] = [
      '#type'          => 'textfield',
      '#title'         => t('vk.com'),
      '#description'   => t("Enter yours vk.com page url. Leave the url field blank to hide this icon."),
      '#default_value' => theme_get_setting('vk_url', 'vanipro'),
  ];

  // Social -> New Social Icons
  $form['social']['social_new_icon'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Add More Social Icons'),
  ];

  $form['social']['social_new_icon']['social_new_icon_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('New Social Icons Code'),
    '#default_value' => theme_get_setting('social_new_icon_code', 'vanipro'),
    '#description'   => t('Please refer to this <a href="https://drupar.com/vanipro-documentation/social-icons-footer" target="_blank">documentation page</a> for social icons code tutorial.'),
  ];

  // Social-> Node sharing option.
  $form['social']['page_share'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Node Sharing on Social Networking Websites'),
  ];
  $form['social']['page_share']['page_share_all'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable Node Sharing Feature'),
    '#default_value' => theme_get_setting('page_share_all', 'vanipro'),
    '#description'   => t("Check this option to enable site wide social sharing. Below you can enable or disable for individual content type and frontpage. Uncheck to disable this feature for all pages."),
  ];

  $form['social']['page_share']['page_share_front'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Share Homepage'),
    '#default_value' => theme_get_setting('page_share_front', 'vanipro'),
    '#description'   => t("Check this option to show social sharing buttons (facebook, twitter, Instagram etc) on <strong>Homepage</strong>. Uncheck to hide."),
  ];

  $form['social']['page_share']['page_share_page'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Share Page Content Type'),
    '#default_value' => theme_get_setting('page_share_page', 'vanipro'),
    '#description'   => t("Check this option to show social sharing buttons (facebook, twitter, Instagram etc) on <strong>Basic page</strong> content type nodes. Uncheck to hide."),
  ];

  $form['social']['page_share']['page_share_article'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Share Article Content Type'),
    '#default_value' => theme_get_setting('page_share_article', 'vanipro'),
    '#description'   => t("Check this option to show social sharing buttons (facebook, twitter, Instagram etc) on <strong>Article</strong> content type nodes. Uncheck to hide."),
  ];

  $form['social']['page_share']['page_share_book'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Share Book Pages'),
    '#default_value' => theme_get_setting('page_share_book', 'vanipro'),
    '#description'   => t("Check this option to show social sharing buttons (facebook, twitter, Instagram etc) on <strong>Book</strong> type nodes. Uncheck to hide."),
  ];

  $form['social']['page_share']['page_share_other'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Share Other Content Types'),
    '#default_value' => theme_get_setting('page_share_other', 'vanipro'),
    '#description'   => t("Check this option to show social sharing buttons (facebook, twitter, Instagram etc) on other content type nodes. Uncheck to hide."),
  ];

  /**
   * Settings under slider tab.
   */
  // Show or hide slider on homepage.
  $form['slider']['slider_enable_option'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Enable Slider'),
  ];

  $form['slider']['slider_enable_option']['slider_show'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show Slider on Homepage'),
    '#default_value' => theme_get_setting('slider_show', 'vanipro'),
    '#description'   => t("Check this option to show slider on homepage. Uncheck to hide."),
  ];

  // Homepage slider -> Animated Circles
  $form['slider']['slider_circle_option'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Animated Circles in Slider'),
  ];

  $form['slider']['slider_circle_option']['slider_circle'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show Animated Circles in Slider'),
    '#default_value' => theme_get_setting('slider_circle', 'vanipro'),
    '#description'   => t("Check this option to show animated circles in slider. Uncheck to hide."),
  ];

  // Slider -> Slider interval time.
  $form['slider']['time'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Slider Interval Time'),
  ];
  $form['slider']['time']['slider_time'] = [
    '#type'          => 'number',
    '#title'         => t('Autoplay Interval Time'),
    '#default_value' => theme_get_setting('slider_time', 'vanipro'),
    '#description'   => t('Default value is 10000, this means 10 seconds.'),
  ];

  // Slider -> Slider dots option.
  $form['slider']['slider_dots_field'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Slider Dots Navigation'),
  ];
  $form['slider']['slider_dots_field']['slider_dots'] = [
    '#type'          => 'select',
    '#title'         => t('Show or Hide Slider Dots Navigation'),
    '#options' => array(
      'true' => t('Show'),
      'false' => t('Hide'),),
    '#default_value' => theme_get_setting('slider_dots', 'vanipro'),
    '#description'   => t('Show or hide slider dots navigation that appears at the bottom of slider.'),
  ];

  // Slider -> Slider nav option.
  $form['slider']['slider_nav_field'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Slider Nav Arrow'),
  ];
  $form['slider']['slider_nav_field']['slider_nav'] = [
    '#type'          => 'select',
    '#title'         => t('Show or Hide Slider Nav Arrow'),
    '#options' => array(
      'true' => t('Show'),
      'false' => t('Hide'),),
    '#default_value' => theme_get_setting('slider_nav', 'vanipro'),
    '#description'   => t('Show or hide slider navigation arrow for previous and next slide.'),
  ];

  // Slider -> code source
  $form['slider']['slider_code_source_section'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Slider Code Source'),
  ];
  $form['slider']['slider_code_source_section']['slider_code_source'] = [
    '#type'          => 'select',
    '#title'         => t('Select The Source For Slider Code'),
    '#options' => array(
      'code_from_settings' => t('Below Textarea'),
      'code_from_block' => t('Drupal Block'),),
    '#default_value' => theme_get_setting('slider_code_source', 'vanipro'),
    '#description'   => t('<p><ul><li>If your website is single language, use <strong>Below Textarea</strong> for slider code.</li><li>If your website is multilingual, select <strong>Drupal Block</strong> for slider code.</li></ul></p>'),
  ];
  $form['slider']['slider_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('Slider Code'),
    '#default_value' => theme_get_setting('slider_code', 'vanipro'),
    '#description'   => t('Please refer to this <a href="https://drupar.com/vanipro-documentation/homepage-slider" target="_blank">documentation page</a> for slider code tutorial.'),
  ];

  // Settings under header tab.
  // Header -> sticky header.
  $form['header']['sticky_header'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Sticky Header'),
  ];
  $form['header']['sticky_header']['sticky_header_option'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable Sticky Header'),
    '#default_value' => theme_get_setting('sticky_header_option', 'vanipro'),
    '#description'   => t("Check this option to enable sticky header. Uncheck to disable."),
  ];
  // Header -> Animated circles
  $form['header']['circle'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Animated Circles in Header'),
  ];
  $form['header']['circle']['header_circle'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable Animated Circles'),
    '#default_value' => theme_get_setting('header_circle', 'vanipro'),
    '#description'   => t("Check this option to show animated circles in header. Uncheck to disable."),
  ];
  // Settings under sidebar.
  $form['sidebar']['front_sidebar_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Homepage Sidebar'),
  ];
  $form['sidebar']['front_sidebar_section']['front_sidebar'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show Sidebars On Homepage'),
    '#default_value' => theme_get_setting('front_sidebar', 'vanipro'),
    '#description'   => t("<p>Check this option to enable left and right sidebar on homepage.</p><hr /><br /><strong>Homepage Content</strong> block regions will always be full width."),
  ];
  $form['sidebar']['animated_sidebar'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Animated Sidebar'),
  ];
  $form['sidebar']['animated_sidebar']['animated_sidebar_option'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable animated sidebar'),
    '#default_value' => theme_get_setting('animated_sidebar_option', 'vanipro'),
    '#description'   => t("Check this option to enable animated sidebar feature. Uncheck to hide.<br />Please refer to this tutorial for details. <a href='https://drupar.com/vanipro-documentation/animated-sidebar' target='_blank'>How To Create Animated Sidebar</a>"),
  ];
  /**
   * Content
   */
  $form['content']['content_tab'] = [
    '#type'  => 'vertical_tabs',
  ];
  // content -> Homepage  content
  $form['content_tab']['home_content'] = [
    '#type'        => 'details',
    '#title'       => t('Homepage content'),
    '#description' => t('Please follow this tutorial to add content on homepage. <a href="https://drupar.com/node/955" target="_blank">How to add content on homepage</a>'),
    '#group' => 'content_tab',
  ];
  // content -> Page loader
  $form['content_tab']['preloader_section'] = [
    '#type'        => 'details',
    '#title'       => t('Pre Page Loader'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  $form['content_tab']['preloader_section']['preloader'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Pre Page Loader'),
  ];
  $form['content_tab']['preloader_section']['preloader']['preloader_option'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show a loading icon before page loads.'),
    '#default_value' => theme_get_setting('preloader_option', 'vanipro'),
    '#description'   => t("Check this option to show a cool animated image until your website is loading. Uncheck to disable this feature."),
  ];
  // content -> Animated Content
  $form['content_tab']['animated_content_section'] = [
    '#type'        => 'details',
    '#title'       => t('Animated Page Content'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  $form['content_tab']['animated_content_section']['animated_content_in_view'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Animated Page Content'),
    '#description'   => t('<p><hr /></p><p>Please visit this tutorial page for details. <a href="https://drupar.com/vanipro-documentation/how-create-animated-content" target="_blank">How to create animated content</a>.</p>'),
  ];
  $form['content_tab']['animated_content_section']['animated_content_in_view']['animated_content'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable Animated Page Content when in view'),
    '#default_value' => theme_get_setting('animated_content', 'vanipro'),
    '#description'   => t("Check this option to enable animated page content when in view. Uncheck to disable this feature."),
  ];

  // Content -> Google Fonts.
  $form['content_tab']['font'] = [
    '#type'        => 'details',
    '#title'       => t('Fonts'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  $form['content_tab']['font']['google_font_section'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Google Fonts'),
  ];
  $form['content_tab']['font']['google_font_section']['google_font'] = [
    '#type'          => 'select',
    '#title'         => t('Select Google Fonts Location'),
    '#options' => array(
    	'local' => t('Local Self Hosted'),
      'googlecdn' => t('Google CDN Server')
    ),
    '#default_value' => theme_get_setting('google_font'),
    '#description'   => t('VaniPro theme uses following Google fonts: Open Sans and Roboto. You can serve these fonts locally or from Google server.'),
  ];

  // Settings under content tab -> Font Icons.
  $form['content_tab']['icon'] = [
    '#type'        => 'details',
    '#title'       => t('Fonts Icons'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  $form['content_tab']['icon']['fontawesome4'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Font Awesome 4'),
  ];
  $form['content_tab']['icon']['fontawesome4']['font_icon_fontawesome'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Font Awesome 4'),
    '#default_value' => theme_get_setting('font_icon_fontawesome', 'vanipro'),
    '#description'   => t('Check this option to enable FontAwesome font icons v4. Uncheck to disable.<br /><br />Vanipro theme has included FontAwesome v4.7.0 font icons. You can use any FontAwesome font icon with vanipro theme.<br />Please visit this tutorial page for details. <a href="https://drupar.com/custom-shortcodes-set-two/fontawesome-font-icons" target="_blank">How To Use FontAwesome Font Icons</a>.'),
  ];
  $form['content_tab']['icon']['fontawesome5'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Font Awesome 5'),
  ];
  $form['content_tab']['icon']['fontawesome5']['fontawesome_five'] = [
    '#type'          => 'checkbox',
    '#title'         => t('FontAwesome 5 Font Icons'),
    '#default_value' => theme_get_setting('fontawesome_five', 'vanipro'),
    '#description'   => t("<p>Check this option to enable FontAwesome 5 Font Icons. Uncheck to disable.</p><hr /><p>Please use either FontAwesome 5 or FontAwesome 6</p>"),
  ];
  $form['content_tab']['icon']['fontawesome6'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Font Awesome 6'),
  ];
  $form['content_tab']['icon']['fontawesome6']['fontawesome_six'] = [
    '#type'          => 'checkbox',
    '#title'         => t('FontAwesome 6 Font Icons'),
    '#default_value' => theme_get_setting('fontawesome_six', 'vanipro'),
    '#description'   => t("<p>Check this option to enable FontAwesome 6 Font Icons. Uncheck to disable.</p><hr /><p>Please use either FontAwesome 5 or FontAwesome 6</p>"),
  ];
  // content -> Font icons -> Bootstrap Font Icons
  $form['content_tab']['icon']['bootstrap_icons'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Bootstrap Font Icons'),
  ];
  $form['content_tab']['icon']['bootstrap_icons']['bootstrapicons'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable Bootstrap Icons'),
    '#default_value' => theme_get_setting('bootstrapicons'),
    '#description'   => t('Check this option to enable Bootstrap Font Icons. Read more about <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a>'),
  ];
  $form['content_tab']['icon']['material_icon'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Google Material Font Icons'),
  ];
  $form['content_tab']['icon']['material_icon']['font_icon_material'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Google Material Font Icons'),
    '#default_value' => theme_get_setting('font_icon_material', 'vanipro'),
    '#description'   => t('Check this option to enable Google Material font icons. Uncheck to disable.<br /><br /><br />VaniPro theme has included Google material font icons. You can use any Google material icon with vanipro theme.<br />Please visit this tutorial page for details. <a href="https://drupar.com/vanipro-documentation/how-use-google-material-font-icons" target="_blank">How To Use Google Material Font Icons</a>.'),
  ];
  // Content -> Comment
  $form['content_tab']['comment_tab'] = [
    '#type'        => 'details',
    '#title'       => t('Comment'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  $form['content_tab']['comment_tab']['comment'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Comment'),
  ];

  $form['content_tab']['comment_tab']['comment']['comment_user_pic'] = [
    '#type'          => 'checkbox',
    '#title'         => t('User Picture in comments'),
    '#default_value' => theme_get_setting('comment_user_pic', 'vanipro'),
    '#description'   => t("Check this option to show user picture in comment. Uncheck to hide."),
  ];
  // Content -> Node
  $form['content_tab']['node_tab'] = [
    '#type'        => 'details',
    '#title'       => t('Node'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  $form['content_tab']['node_tab']['node'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Node'),
  ];

  $form['content_tab']['node_tab']['node']['node_author_pic'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Node Author Picture'),
    '#default_value' => theme_get_setting('node_author_pic', 'vanipro'),
    '#description'   => t("Check this option to show node author picture in submitted details. Uncheck to hide."),
  ];
  $form['content_tab']['node_tab']['node']['node_tags'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Node Tags'),
    '#default_value' => theme_get_setting('node_tags', 'vanipro'),
    '#description'   => t("Check this option to show node tags (if any) in submitted details. Uncheck to hide."),
  ];

  // Settings under footer tab.
  // Scroll to top.
  $form['footer']['scrolltotop'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Scroll To Top'),
  ];

  $form['footer']['scrolltotop']['scrolltotop_on'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable scroll to top feature.'),
    '#default_value' => theme_get_setting('scrolltotop_on', 'vanipro'),
    '#description'   => t("Check this option to enable scroll to top feature. Uncheck to disable this feature and hide scroll to top icon."),
  ];

  // Footer -> Copyright.
  $form['footer']['copyright'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Website Copyright Text'),
  ];

  $form['footer']['copyright']['copyright_text'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show website copyright text in footer.'),
    '#default_value' => theme_get_setting('copyright_text', 'vanipro'),
    '#description'   => t("Check this option to show website copyright text in footer. Uncheck to hide."),
  ];

  // Footer -> copyright -> custom copyright text
  $form['footer']['copyright']['copyright_text_custom'] = [
    '#type'          => 'textarea',
    '#title'         => t('Custom Copyright Text'),
    '#default_value' => theme_get_setting('copyright_text_custom', 'vanipro'),
    '#description'   => t("Enter custom copyright text. Leave it blank to show default copyright text."),
  ];

  // Footer -> Cookie.
  $form['footer']['cookie'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Cookie Consent message'),
  ];

  $form['footer']['cookie']['cookie_message'] = [
    '#type'          => 'checkbox',
    '#title'       => t('Show Cookie Consent Popup Message'),
    '#default_value' => theme_get_setting('cookie_message', 'vanipro'),
    '#description'   => t('Required to place a Cookie Consent message on your site, as per the EU cookie law? Make your website EU Cookie Law Compliant.<br />According to EU cookies law, websites need to get consent from visitors to store or retrieve cookies.'),
  ];

  // Footer -> Cookie -> custom cookie message.
  $form['footer']['cookie']['cookie_message_custom'] = [
    '#type'          => 'textarea',
    '#title'         => t('Custom Cookie Consent Message'),
    '#default_value' => theme_get_setting('cookie_message_custom', 'vanipro'),
    '#description'   => t("Enter Cookie Consent Message. Leave it blank to show default message text."),
  ];
  /**
   * Insert Codes
   */
  $form['insert_codes']['insert_codes_tab'] = [
    '#type'  => 'vertical_tabs',
  ];
  // Insert Codes -> Head
  $form['insert_codes']['head'] = [
    '#type'        => 'details',
    '#title'       => t('Head'),
    '#description' => t('<h3>Insert Codes Before &lt;/HEAD&gt;</h3><hr />'),
    '#group' => 'insert_codes_tab',
  ];
  // Insert Codes -> Body
  $form['insert_codes']['body'] = [
    '#type'        => 'details',
    '#title'       => t('Body'),
    '#group' => 'insert_codes_tab',
  ];
  // Insert Codes -> CSS
  $form['insert_codes']['css'] = [
    '#type'        => 'details',
    '#title'       => t('CSS Codes'),
    '#group'       => 'insert_codes_tab',
  ];
  // Insert Codes -> Head -> Head codes
  $form['insert_codes']['head']['insert_head'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable custom codes in &lt;head&gt; section'),
    '#default_value' => theme_get_setting('insert_head', 'vanipro'),
    '#description'   => t("Check this option to enable custom codes in &lt;head&gt; section. Uncheck to disable this feature."),
  ];
  $form['insert_codes']['head']['head_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('&lt;head&gt; Codes'),
    '#default_value' => theme_get_setting('head_code', 'vanipro'),
    '#description'   => t("Please enter your custom codes for &lt;head&gt; section. These codes will be inserted just before <strong>&lt;/head&gt;</strong>."),
  ];
  // Insert Codes -> Body -> Body start codes
  $form['insert_codes']['body']['insert_body_start_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Insert code after &lt;BODY&gt; tag'),
  ];
  $form['insert_codes']['body']['insert_body_start_section']['insert_body_start'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable custom codes after &lt;body&gt; tag'),
    '#default_value' => theme_get_setting('insert_body_start', 'vanipro'),
    '#description'   => t("Check this option to enable custom codes after &lt;body&gt; tag. Uncheck to disable this feature."),
  ];
  $form['insert_codes']['body']['insert_body_start_section']['body_start_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('Codes'),
    '#default_value' => theme_get_setting('body_start_code', 'vanipro'),
    '#description'   => t("Please enter your custom codes after &lt;body&gt; tag. These codes will be inserted just after <strong>&lt;body&gt;</strong> tag."),
  ];
  // Insert Codes -> Body -> Body end codes
  $form['insert_codes']['body']['insert_body_end_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Insert code before &lt;/BODY&gt; tag'),
  ];
  $form['insert_codes']['body']['insert_body_end_section']['insert_body_end'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable custom codes before &lt;/body&gt; tag.'),
    '#default_value' => theme_get_setting('insert_body_end', 'vanipro'),
    '#description'   => t("Check this option to enable custom codes before &lt;/body&gt; tag. Uncheck to disable this feature."),
  ];
  $form['insert_codes']['body']['insert_body_end_section']['body_end_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('Codes'),
    '#default_value' => theme_get_setting('body_end_code', 'vanipro'),
    '#description'   => t("Please enter your custom codes before &lt;/body&gt; tag. These codes will be inserted just before <strong>&lt;/body&gt;</strong>."),
  ];
  // Insert Codes -> css
  $form['insert_codes']['css']['css_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Custom Styling'),
  ];

  $form['insert_codes']['css']['css_section']['styling'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable custom css'),
    '#default_value' => theme_get_setting('styling', 'vanipro'),
    '#description'   => t("Check this option to enable custom styling. Uncheck to disable this feature.<br />Please refer to this tutorial page. <a href='https://drupar.com/vanipro-documentation/custom-css' target='_blank'>How To Use Custom Styling</a>"),
  ];

  $form['insert_codes']['css']['css_section']['styling_code'] = [
    '#type'          => 'textarea',
    '#rows'          => 20,
    '#title'         => t('Custom CSS Codes'),
    '#default_value' => theme_get_setting('styling_code', 'vanipro'),
    '#description'   => t('Please enter your custom css codes in this text box. You can use it to customize the appearance of your site.<br />Please refer to this tutorial for detail: <a href="https://drupar.com/vanipro-documentation/custom-css" target="_blank">Custom CSS</a>'),
  ];
  /**
   * Settings under update tab.
   */
  $form['update']['update_version'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Current Theme Version'),
    '#description' => t("$ver"),
  ];
  $form['update']['update_info'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Latest VaniPro Version'),
    '#description' => t("<pre>$theme_update_info</pre>"),
  ];
  /**
   * Settings under License tab.
   */
  $form['license']['info'] = [
    '#type'        => 'fieldset',
    '#title'       => t('License Type'),
    '#description' => t('<p>Your theme license is: <strong>Single Domain License</strong></p>
    <p>You are allowed to use this theme on a single website. For details, please refer to <a href="https://drupar.com/theme-license" target="_blank">Theme License Details</a></p>
    <hr /><br /><a href="https://drupar.com/upgrade/vanipro" target="_blank">Upgrade to unlimited domain license</a>. Upgrade fee is $30 only.'),
  ];
  $form['license']['upgrade'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Upgrade'),
    '#description' => t('<p>You can upgrade to unlimited domain license. Upgrade price is $30 only.</p><p><hr /></p><p><a href="https://drupar.com/upgrade/vanipro" target="_blank">Upgrade to unlimited domain license</a>.</p>'),
  ];

  // Settings under support tab.
  $form['support']['info'] = [
    '#type'        => 'fieldset',
    '#description' => t('<h4>Documentation</h4>
    <p>We have a detailed documentation about how to use theme. Please read the <a href="https://drupar.com/vanipro-documentation" target="_blank">Vanipro Theme Documentation</a>.</p>
    <hr />
    <h4>Open Support Ticket</h4>
    <p>For any support related to VaniPro theme, please <a href="https://drupar.com/node/add/ticket" target="_blank">open a support ticket</a>.</p>
    <hr />
    <h4>Contact Us</h4>
    <p>If you need some specific customization in theme, please contact us<br><a href="https://drupar.com/contact" target="_blank">drupar.com/contact</a></p>'),
  ];  
// End form.
}