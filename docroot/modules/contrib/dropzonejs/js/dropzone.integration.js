/**
 * @file
 * dropzone.integration.js
 *
 * Defines the behaviors needed for dropzonejs integration.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.dropzonejs = Drupal.dropzonejs || {};

  Drupal.dropzonejs.instances = {};

  Drupal.dropzonejs.chunksUploaded = function (file, done, el) {
    var selector = $(el);
    var input = selector.siblings('input');
    var url = input.attr('data-finalize-path');

    // Send request to merge chunks for current file.
    Drupal.ajax({
      url: url,
      base: false,
      element: false,
      progress: false,
      submit: {
        filename: file.upload.filename,
        dzuuid: file.upload.uuid,
        dztotalfilesize: file.size,
        dztotalchunkcount: file.upload.totalChunkCount
      },
      success: function (response) {
        // Call dropzone's done callback. (see https://www.dropzonejs.com/#config-chunksUploaded)
        // The file is transliterated on upload. The element has to reflect
        // the real filename.
        file.processedName = response.result;

        done();
      },
      error: function (xhr, url) {
        var instance = Drupal.dropzonejs.instances[selector.attr('id')];
        var response = $.parseJSON(xhr.responseText);
        instance._errorProcessing([file], response.error, xhr);
      }
    }).execute();
  };

  /* global Dropzone */
  Drupal.behaviors.dropzonejsIntegraion = {
    attach: function (context) {
      Dropzone.autoDiscover = false;

      $('.dropzone-enable', context).each(function () {
        var selector = $(this);
        selector.addClass('dropzone');

        // selector.addClass('dropzone');
        var input = selector.siblings('input');

        // Initiate dropzonejs.
        var config = {
          url: input.attr('data-upload-path'),
          addRemoveLinks: false,
          maxFilesize: drupalSettings.dropzonejs.maxFilesize,
          dictDefaultMessage: Drupal.t('Drop files here to upload'),
          dictFallbackMessage: Drupal.t('Your browser does not support drag\'n\'drop file uploads.'),
          dictFallbackText: Drupal.t('Please use the fallback form below to upload your files like in the olden days.'),
          dictFileTooBig: Drupal.t('File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.'),
          dictInvalidFileType: Drupal.t('You can\'t upload files of this type.'),
          dictResponseError: Drupal.t('Server responded with {{statusCode}} code.'),
          dictCancelUpload: Drupal.t('Cancel upload'),
          dictCancelUploadConfirmation: Drupal.t('Are you sure you want to cancel this upload?'),
          dictRemoveFile: Drupal.t('Remove file'),
          dictMaxFilesExceeded: Drupal.t('You can not upload any more files.'),
          dictFileSizeUnits: {
            tb: Drupal.t('TB'),
            gb: Drupal.t('GB'),
            mb: Drupal.t('MB'),
            kb: Drupal.t('KB'),
            b: Drupal.t('b')
          },
        };
        var instanceConfig = drupalSettings.dropzonejs.instances[selector.attr('id')];

        if (instanceConfig.chunking) {
          var el = this;
          config.chunksUploaded = function(file, done) {
            Drupal.dropzonejs.chunksUploaded(file, done, el);
          };
        }

        // If DropzoneJS instance is already registered on Element. There is no
        // need to register it again.
        if ($(once('register-dropzonejs', selector)).length !== selector.length) {
          return;
        }

        // If instance exists for configuration, but it's detached from element
        // then destroy detached instance and create new instance.
        if (instanceConfig.instance !== void 0) {
          instanceConfig.instance.destroy();
        }

        // Initialize DropzoneJS instance for element.
        var dropzoneInstance = new Dropzone('#' + selector.attr('id'), $.extend({}, instanceConfig, config));

        // Other modules might need instances.
        drupalSettings['dropzonejs']['instances'][selector.attr('id')]['instance'] = dropzoneInstance;

        dropzoneInstance.on('addedfile', function (file) {
          file._removeIcon = Dropzone.createElement("<div class='dropzonejs-remove-icon' title='Remove'></div>");
          file.previewElement.appendChild(file._removeIcon);
          file._removeIcon.addEventListener('click', function () {
            dropzoneInstance.removeFile(file);
          });
        });

        // React on add file. Add only accepted files.
        dropzoneInstance.on('success', function (file, response) {
          var uploadedFilesElement = selector.siblings(':hidden');
          var currentValue = uploadedFilesElement.attr('value') || '';

          // Chunked uplaods get an empty response.
          // (see https://gitlab.com/meno/dropzone/-/blob/master/src/dropzone.js#L1978).
          if (response) {
            // The file is transliterated on upload. The element has to reflect
            // the real filename.
            file.processedName = response.result;
          }

          uploadedFilesElement.attr('value', currentValue + file.processedName + ';');
        });

        // React on file removing.
        dropzoneInstance.on('removedfile', function (file) {
          var uploadedFilesElement = selector.siblings(':hidden');
          var currentValue = uploadedFilesElement.attr('value');

          // Remove the file from the element.
          if (currentValue.length) {
            var fileNames = currentValue.split(';');
            for (var i in fileNames) {
              if (fileNames[i] === file.processedName) {
                fileNames.splice(i, 1);
                break;
              }
            }

            var newValue = fileNames.join(';');
            uploadedFilesElement.attr('value', newValue);
          }
        });

        // React on maxfilesexceeded. Remove all rejected files.
        dropzoneInstance.on('maxfilesexceeded', function () {
          var rejectedFiles = dropzoneInstance.getRejectedFiles();
          for (var i = 0; i < rejectedFiles.length; i++) {
            dropzoneInstance.removeFile(rejectedFiles[i]);
          }
        });

        Drupal.dropzonejs.instances[selector.attr('id')] = dropzoneInstance;
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
