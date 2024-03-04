/**
 * @module captioned-video/captionedvideodui
 */

/* eslint-disable import/no-extraneous-dependencies */

import { icons } from 'ckeditor5/src/core';
import {createDropdown} from 'ckeditor5/src/ui';
import { MediaEmbedUI } from "../mediaembed";
import CaptionedVideoEditing from "./captionedvideoediting";
import MediaFormView from "../mediaembed/ui/mediaformview";
import videoEmbedIcon from '../../../../icons/videoembed.svg';
import {getFormValidators} from "../mediaembed/mediaembedui";

/**
 * The captioned video UI plugin.
 *
 * @private
 */
export default class CaptionedVideoUI extends MediaEmbedUI {

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ CaptionedVideoEditing ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'CaptionedVideoUI';
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const command = editor.commands.get( 'captionedVideo' );
    const registry = editor.plugins.get( CaptionedVideoEditing ).registry;

    editor.ui.componentFactory.add( 'captionedVideo', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'video', videoEmbedIcon, 'Insert Video' );
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );

    editor.ui.componentFactory.add( 'videoEdit', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'video', icons.pencil, 'Edit Video Url');
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );

  }

}
