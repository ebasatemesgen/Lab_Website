import { Plugin } from 'ckeditor5/src/core';
import {  ButtonView, ContextualBalloon, clickOutsideHandler  } from 'ckeditor5/src/ui';
import icon from '../icons/drupalfontawesome.svg';

export default class FontAwesomeUI extends Plugin {
  init() {
    this.drupal = require('Drupal');
    const editor = this.editor;
    const activeFormat = editor.sourceElement.getAttribute('data-editor-active-text-format');

    editor.ui.componentFactory.add('fontAwesome', (locale) => {
      const buttonView = new ButtonView(locale);
      const command = editor.commands.get( 'insertFontAwesomeIcon' );

      buttonView.set({
        label: editor.t('Insert Fontawesome Icon'),
        icon,
        tooltip: true,
      });

      buttonView.bind('isEnabled').to( command, 'isEnabled' );

      this.listenTo(buttonView, 'execute', () => {
        const dialogSettings = {
          title: 'FontAwesome',
          dialogClass: 'fontawesome-icon-dialog',
        };

        this.drupal.ckeditor5.openDialog(this.drupal.url(`fontawesome/dialog/icon/${activeFormat}`), ({attributes, tag})=>{
          editor.execute('insertFontAwesomeIcon', attributes, tag);
        }, dialogSettings);
      } );

      return buttonView;
    });
  }
}
