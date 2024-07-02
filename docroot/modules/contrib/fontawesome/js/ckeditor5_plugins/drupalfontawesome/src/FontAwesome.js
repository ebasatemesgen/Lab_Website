import FontAwesomeEditing from './FontAwesomeEditing';
import FontAwesomeUI from './FontAwesomeUI';
import { Plugin } from 'ckeditor5/src/core';

export default class FontAwesome extends Plugin {
  static get requires() {
    return [FontAwesomeEditing, FontAwesomeUI];
  }
}
