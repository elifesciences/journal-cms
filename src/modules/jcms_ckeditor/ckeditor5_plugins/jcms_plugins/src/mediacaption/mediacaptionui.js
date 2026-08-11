/**
 * @module media-caption/mediacaptionui
 */

import { Plugin, icons } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import MediaCaptionUtils from './mediacaptionutils';

/**
 * The media caption UI plugin. It introduces the `'toggleMediaCaption'` UI button.
 */
export default class MediaCaptionUI extends Plugin {
  /**
   * @inheritDoc
   */
  static get requires() {
    return [ MediaCaptionUtils ];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'MediaCaptionUI';
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const editingView = editor.editing.view;
    const mediaCaptionUtils = editor.plugins.get( 'MediaCaptionUtils' );
    const t = editor.t;

    editor.ui.componentFactory.add( 'toggleMediaCaption', locale => {
      const command = editor.commands.get( 'toggleMediaCaption' );
      const view = new ButtonView( locale );

      view.set( {
        icon: icons.caption,
        tooltip: true,
        isToggleable: true
      } );

      view.bind( 'isOn', 'isEnabled' ).to( command, 'value', 'isEnabled' );
      view.bind( 'label' ).to( command, 'value', value => value ? t( 'Toggle caption off' ) : t( 'Toggle caption on' ) );

      this.listenTo( view, 'execute', () => {
        editor.execute( 'toggleMediaCaption', { focusCaptionOnShow: true } );

        // Scroll to the selection and highlight the caption if the caption showed up.
        const modelCaptionElement = mediaCaptionUtils.getCaptionFromModelSelection( editor.model.document.selection );

        if ( modelCaptionElement ) {
          const figcaptionElement = editor.editing.mapper.toViewElement( modelCaptionElement );

          editingView.scrollToTheSelection();

          editingView.change( writer => {
            writer.addClass( 'media__caption_highlighted', figcaptionElement );
          } );
        }

        editor.editing.view.focus();
      } );

      return view;
    } );
  }
}
