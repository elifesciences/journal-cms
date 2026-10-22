/* eslint-disable import/no-extraneous-dependencies */

import { Command } from 'ckeditor5/src/core';

/**
 * The eLife button command.
 *
 * This command adds an eLife button.
 *
 * @extends module:core/command~Command
 *
 * @private
 */
export default class InsertElifeButtonCommand extends Command {
  /**
   * @inheritdoc
   */
  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const selectedElement = selection.getSelectedElement();

    // Determine if the cursor (selection) is in a position where adding a
    // elifeButton is permitted. This is based on the schema of the model(s)
    // currently containing the cursor.
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'elifeButton',
    );

    this.value = {
      url: selectedElement ? selectedElement.getAttribute( 'url' ) : null,
      text: selectedElement && selectedElement.childCount > 0 ? selectedElement.getChild(0).data : null,
    };

    // If the cursor is not in a location where a elifeButton can be added, return
    // null so the addition doesn't happen.
    this.isEnabled = allowedIn !== null;
  }

  /**
   * Executes the command.
   *
   * @example
   *   editor.execute('toggleMediaModalCommand');
   *
   * @param {Object} [options]
   *   Options for the executed command.
   *
   * @fires execute
   */
  execute(url, text) {
    const { model } = this.editor;
    const selection = model.document.selection;
    const selectedElement = selection.getSelectedElement();

    if ( selectedElement && selectedElement.is( 'element', 'elifeButton' ) ) {
      model.change( writer => {
        if (selectedElement.childCount > 0) {
          const node = selectedElement.getChild(0);
          writer.remove(node);
        }
        writer.setAttribute( 'url', url, selectedElement );
        writer.appendText(text, selectedElement);
      } );
    } else {
      model.change((writer) => {
        // Insert <elifeButton>*</elifeButton> at the current selection position
        // in a way that will result in creating a valid model structure.
        const elifeButton = writer.createElement('elifeButton');
        writer.appendText(text, elifeButton);
        writer.setAttribute( 'url', url, elifeButton );
        model.insertObject(elifeButton, selection, null,{
          setSelection: 'on',
          findOptimalPosition: 'auto',
        });
      });
    }
  }
}
