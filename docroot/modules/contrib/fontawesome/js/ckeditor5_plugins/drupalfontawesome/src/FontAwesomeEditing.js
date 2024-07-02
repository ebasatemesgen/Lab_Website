import { Plugin } from 'ckeditor5/src/core';
import { toWidget, toWidgetEditable } from 'ckeditor5/src/widget';
import InsertFontAwesomeIcon from './InsertFontAwesomeIcon';


export default class FontAwesomeEditing extends Plugin {

  init() {
    this._defineSchema();
    this._defineConverters();
    this.editor.commands.add(
      'insertFontAwesomeIcon',
      new InsertFontAwesomeIcon(this.editor),
    );
  }

  _defineSchema() {
    const schema = this.editor.model.schema;

    schema.register('fontAwesomeIconInline', {
      inheritAllFrom: '$inlineObject',
      allowAttributes: ['class', 'data-fa-transform', 'data-tag'],
    });

    schema.register('fontAwesomeIcon', {
      inheritAllFrom: '$inlineObject',
      allowAttributes: ['class', 'data-fa-transform', 'data-tag'],
    });
  }

  _defineConverters() {
    const { conversion } = this.editor;

    conversion.for('upcast').elementToElement({
      view: {
        name: 'span',
        classes: 'fontawesome-icon-inline',
      },
      model: (viewElement, { writer }) => {
        const childElement = viewElement.getChild(0);
        const fontAwesomeIconInline = writer.createElement('fontAwesomeIconInline', childElement.getAttributes());
        writer.setAttribute('data-tag', childElement.name, fontAwesomeIconInline);
        return fontAwesomeIconInline;
      }
    });

    conversion.for('upcast').elementToElement({
      view: {
        name: /^(span|i)$/,
        classes: /^(fa|fa-classic|fa-sharp|fas|fa-solid|far|fa-regular|fab|fa-brands)$/,
      },
      model: ( viewElement, { writer } ) => {
        const fontAwesomeIcon = writer.createElement('fontAwesomeIcon', viewElement.getAttributes());
        writer.setAttribute('data-tag', viewElement.name, fontAwesomeIcon);
        return fontAwesomeIcon;
      }
    });

    // Prevent ckeditor 5 from converting fontawesome icons to attributes.
    conversion.for('upcast').elementToAttribute({
      view: {
        name: /^(span|i)$/,
        classes: /(fa-)\w+/,
      },
      model: {
        key: null,
      },
      converterPriority: 'high',
    });

    conversion.for('dataDowncast').elementToElement({
      model: {
        name: 'fontAwesomeIconInline',
        attributes: ['class', 'data-fa-transform', 'data-tag']
      },
      view: (modelElement, { writer }) => {
        return createFontAwesomeIconInlineView(modelElement, writer);
      }
    });

    conversion.for('dataDowncast').elementToElement( {
      model: {
        name: 'fontAwesomeIcon',
        attributes: ['class', 'data-fa-transform', 'data-tag']
      },
      view: (modelElement, { writer }) => {
        return createFontAwesomeIconInlineView(modelElement, writer);
      }
    } );

    conversion.for('editingDowncast').elementToElement( {
      model: {
        name: 'fontAwesomeIconInline',
        attributes: ['class', 'data-fa-transform', 'data-tag']
      },
      view: (modelElement, { writer}) => {
        const icon = createFontAwesomeIconInlineView(modelElement, writer);
        const widgetElement = writer.createContainerElement('span', {}, [icon]);
        return toWidget(widgetElement, writer);
      }
    } );

    conversion.for('editingDowncast').elementToElement( {
      model: {
        name: 'fontAwesomeIcon',
        attributes: ['class', 'data-fa-transform', 'data-tag']
      },
      view: ( modelElement, { writer} ) => {
        const icon = createFontAwesomeIconInlineView(modelElement, writer);
        const widgetElement = writer.createContainerElement('span', {}, [icon]);
        return toWidget(widgetElement, writer);
      }
    } );

    function createFontAwesomeIconInlineView(modelElement, writer) {
      const tag = modelElement.getAttribute('data-tag');
      const classes = modelElement.getAttribute('class');
      const transforms = modelElement.getAttribute('data-fa-transform');
      return writer.createRawElement('span', { class: 'fontawesome-icon-inline' }, function(domElement) {
        const transformAttribute = transforms ? `data-fa-transform="${transforms}"` : '';
        domElement.innerHTML = `<${tag} class="${classes}" ${transformAttribute}>&nbsp;</${tag}>`;
      });
    }

    function createFontAwesomeIconView(modelElement, writer) {
      const attributes = { class: modelElement.getAttribute('class') };
      const tag = modelElement.getAttribute('data-tag');
      const classes = modelElement.getAttribute('class');
      const transforms = modelElement.getAttribute('data-fa-transform');
      if (transforms) {
        attributes['data-fa-transform'] = transforms;
      }
      return writer.createRawElement('span', [], function (domElement) {
        // domElement.innerHTML = '&nbsp;';
        const transformAttribute = transforms ? `data-fa-transform="${transforms}"` : '';
        domElement.innerHTML = `<${tag} class="${classes}" ${transformAttribute}>&nbsp;</${tag}>`;
      });
    }
  }
}
