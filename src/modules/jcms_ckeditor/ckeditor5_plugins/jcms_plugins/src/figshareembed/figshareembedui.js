/**
 * @module figshare-embed/figshareembedui
 */

/* eslint-disable import/no-extraneous-dependencies */

import { icons } from 'ckeditor5/src/core';
import {createDropdown} from 'ckeditor5/src/ui';
import MediaFormView from "../mediaembed/ui/mediaformview";
import {getFormValidators} from "../mediaembed/mediaembedui";
import figshareEmbedIcon from "../../../../icons/figshareembed.svg";
import {MediaEmbedUI} from "../mediaembed";
import FigshareEmbedEditing from "./figshareembedediting";

/**
 * The figshare UI plugin.
 *
 * @private
 */
export default class FigshareEmbedUI extends MediaEmbedUI {

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ FigshareEmbedEditing ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'FigshareEmbedUI';
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const command = editor.commands.get( 'figshareEmbed' );
    const registry = editor.plugins.get( FigshareEmbedEditing ).registry;

    editor.ui.componentFactory.add( 'figshareEmbed', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'figshare', figshareEmbedIcon, 'Insert Figshare' );
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );

    editor.ui.componentFactory.add( 'figshareEdit', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'figshare', icons.pencil, 'Edit Figshare Url' );
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );
  }

}
