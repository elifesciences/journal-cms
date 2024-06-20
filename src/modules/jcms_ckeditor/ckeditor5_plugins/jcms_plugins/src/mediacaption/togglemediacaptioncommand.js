import { Command } from 'ckeditor5/src/core';

import MediaEmbedEditing from '../mediaembed/mediaembedediting';

/**
 * The toggle image caption command.
 *
 * This command is registered by {@link module:mediacaption/mediacaptionediting~MediaCaptionEditing} as the
 * `'toggleMediaCaption'` editor command.
 *
 * Executing this command:
 *
 * * either adds or removes the media caption of a selected media (depending on whether the caption is present or not),
 * * removes the media caption if the selection is anchored in one.
 *
 *		// Toggle the presence of the caption.
 *		editor.execute( 'toggleMediaCaption' );
 *
 * **Note**: Upon executing this command, the selection will be set on the media if previously anchored in the caption element.
 *
 * **Note**: You can move the selection to the caption right away as it shows up upon executing this command by using
 * the `focusCaptionOnShow` option:
 *
 *		editor.execute( 'toggleMediaCaption', { focusCaptionOnShow: true } );
 *
 * @extends module:core/command~Command
 */
export default class ToggleMediaCaptionCommand extends Command {
  /**
   * @inheritDoc
   */
  refresh() {
    const editor = this.editor;
    const mediaCaptionUtils = editor.plugins.get( 'MediaCaptionUtils' );

    if ( !editor.plugins.has( MediaEmbedEditing ) ) {
      this.isEnabled = false;
      this.value = false;

      return;
    }

    const selection = editor.model.document.selection;
    const selectedElement = selection.getSelectedElement();

    if ( !selectedElement ) {
      const ancestorCaptionElement = mediaCaptionUtils.getCaptionFromModelSelection( selection );

      this.isEnabled = !!ancestorCaptionElement;
      this.value = !!ancestorCaptionElement;

      return;
    }

    this.isEnabled = mediaCaptionUtils.isMedia( selectedElement );

    if ( !this.isEnabled ) {
      this.value = false;
    } else {
      this.value = !!mediaCaptionUtils.getCaptionFromMediaModelElement( selectedElement );
    }
  }

  /**
   * Executes the command.
   *
   *		editor.execute( 'toggleMediaCaption' );
   *
   * @param {Object} [options] Options for the executed command.
   * @param {String} [options.focusCaptionOnShow] When true and the caption shows up, the selection will be moved into it straight away.
   * @fires execute
   */
  execute( options = {} ) {
    const { focusCaptionOnShow } = options;

    this.editor.model.change( writer => {
      if ( this.value ) {
        this._hideMediaCaption( writer );
      } else {
        this._showMediaCaption( writer, focusCaptionOnShow );
      }
    } );
  }

  /**
   * Shows the caption of the `<media>`. Also:
   *
   * @private
   * @param {module:engine/model/writer~Writer} writer
   */
  _showMediaCaption( writer, focusCaptionOnShow ) {
    const model = this.editor.model;
    const selection = model.document.selection;
    const mediaCaptionEditing = this.editor.plugins.get( 'MediaCaptionEditing' );

    let selectedMedia = selection.getSelectedElement();

    const savedCaption = mediaCaptionEditing._getSavedCaption( selectedMedia );

    // Try restoring the caption from the MediaCaptionEditing plugin storage.
    const newCaptionElement = savedCaption || writer.createElement( 'caption' );

    writer.append( newCaptionElement, selectedMedia );

    if ( focusCaptionOnShow ) {
      writer.setSelection( newCaptionElement, 'in' );
    }
  }

  /**
   * Hides the caption of a selected media (or an media caption the selection is anchored to).
   *
   * The content of the caption is stored in the `MediaCaptionEditing` caption registry to make this
   * a reversible action.
   *
   * @private
   * @param {module:engine/model/writer~Writer} writer
   */
  _hideMediaCaption( writer ) {
    const editor = this.editor;
    const selection = editor.model.document.selection;
    const mediaCaptionEditing = editor.plugins.get( 'MediaCaptionEditing' );
    const mediaCaptionUtils = editor.plugins.get( 'MediaCaptionUtils' );
    let selectedMedia = selection.getSelectedElement();
    let captionElement;

    if ( selectedMedia ) {
      captionElement = mediaCaptionUtils.getCaptionFromMediaModelElement( selectedMedia );
    } else {
      captionElement = mediaCaptionUtils.getCaptionFromModelSelection( selection );
      selectedMedia = captionElement.parent;
    }
    mediaCaptionEditing._saveCaption( selectedMedia, captionElement );

    writer.setSelection( selectedMedia, 'on' );
    writer.remove( captionElement );
  }
}
