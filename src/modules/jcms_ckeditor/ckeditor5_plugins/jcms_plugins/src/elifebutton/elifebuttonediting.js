/**
 * @module elife-button/elifebuttonediting
 */

/* eslint-disable import/no-extraneous-dependencies */

import { Plugin } from 'ckeditor5/src/core';
import { Widget, toWidget } from 'ckeditor5/src/widget';
import InsertElifeButtonCommand from "./insertelifebuttoncommand";

/**
 * Model to view and view to model conversions for elife button.
 *
 * @private
 *
 */
export default class ElifeButtonEditing extends Plugin {

  /**
   * @inheritdoc
   */
  static get requires() {
    return [Widget];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'ElifeButtonEditing';
  }

  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    const { conversion } = editor;
    this._defineSchema();
    this._defineConverters();

    editor.commands.add(
      'insertElifeButton',
      new InsertElifeButtonCommand(editor),
    );
  }

  /*
   * This registers the structure that will be seen by CKEditor 5 as
   * <elifebutton data-href="{url}">
   *    {text}
   * </elifebutton>
   *
   * The logic in _defineConverters() will determine how this is converted to
   * markup.
   */
  _defineSchema() {
    // Schemas are registered via the central `editor` object.
    const schema = this.editor.model.schema;

    schema.register('elifeButton', {
      // Behaves like a self-contained object (e.g. an image).
      isObject: true,
      // Allow in places where other blocks are allowed (e.g. directly in the root).
      allowWhere: '$block',
      allowAttributes: ['url']
    });
  }

  /**
   * Converters determine how CKEditor 5 models are converted into markup and
   * vice-versa.
   */
  _defineConverters() {
    // Converters are registered via the central editor object.
    const {conversion} = this.editor;

    // Upcast Converters: determine how existing HTML is interpreted by the
    // editor. These trigger when an editor instance loads.

    // If <elifebutton data-href=""> is present in the existing markup
    // processed by CKEditor, then CKEditor recognizes and loads it as a
    // <elifeButton> with src attribute.
    conversion.for('upcast').elementToElement( {
      view: {
        name: 'elifebutton',
      },
      model: ( viewElement, { writer } ) => {
        const url = viewElement.getAttribute( 'data-href' );
        const text = (viewElement && viewElement.childCount > 0 ) ? viewElement.getChild(0).data : '';
        const modelElement = writer.createElement( 'elifeButton', { url } );
        writer.appendText(text, modelElement);
        return modelElement;
      }
    } );

    // Data Downcast Converters: converts stored model data into HTML.
    // These trigger when content is saved.
    //
    // Instances of <elifeButton> are saved as
    // <elifebutton>{{inner content}}</elifebutton>.
    conversion.for('dataDowncast').elementToElement({
      model: 'elifeButton',
      view: (modelElement, {writer: viewWriter}) => {
        const url = modelElement.getAttribute('url');
        const viewElement = viewWriter.createContainerElement('elifebutton', { 'data-href': url });
        return viewElement;
      },
    });


    // Editing Downcast Converters. These render the content to the user for
    // editing, i.e. this determines what gets seen in the editor. These trigger
    // after the Data Upcast Converters, and are re-triggered any time there
    // are changes to any of the models' properties.
    //
    // Convert the <elifeButton> model into a container widget in the editor UI.
    conversion.for('editingDowncast').elementToElement({
      model: 'elifeButton',
      view: (modelElement, {writer: viewWriter}) => {
        const url = modelElement.getAttribute('url');
        const viewElement = viewWriter.createContainerElement('elifebutton', { 'data-href': url, class: 'elife-button--default'});
        viewWriter.setCustomProperty('elifebutton', true, viewElement);
        return toWidget(viewElement, viewWriter);
      },
    });

  }
}
