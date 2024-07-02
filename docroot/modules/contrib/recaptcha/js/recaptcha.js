(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.reCaptchaFix = {
    attach: function (context, settings) {
      if (context == document) {
         var intervalID = window.setInterval(setAccesibilityLabel, 50);
        function setAccesibilityLabel() {
          var textAreaCheck = document.getElementsByClassName('g-recaptcha-response');
          if (textAreaCheck.length > 0) {
               $(textAreaCheck).attr('aria-labelledby', 'g-recaptcha-response');
               console.log($(textAreaCheck));
            clearInterval(intervalID);
          }
        }
      }
    }
  };
})(jQuery, Drupal);
