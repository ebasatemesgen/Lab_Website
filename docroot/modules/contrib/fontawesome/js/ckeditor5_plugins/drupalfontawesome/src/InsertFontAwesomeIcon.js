import { Command } from 'ckeditor5/src/core';

export default class InsertFontAwesomeIcon extends Command {
  constructor( locale ) {
    super( locale );
  }

  execute(attributes, tag) {
    const { model } = this.editor;

    model.change((writer) => {
      model.insertContent(createFontAwesomeIcon(writer, attributes, tag));
    });
  }

  refresh() {
    const { model } = this.editor;
    const { selection } = model.document;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'fontAwesomeIcon',
    );

    this.isEnabled = allowedIn !== null;
  }
}

function createFontAwesomeIcon(writer, attributes, tag) {
  const fontAwesomeIconElement = writer.createElement('fontAwesomeIconInline', attributes);
  writer.setAttribute('data-tag', tag, fontAwesomeIconElement);
  return fontAwesomeIconElement;
}
