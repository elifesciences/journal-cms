/**
 * @module twitter-embed/twitterembedui
 */

/* eslint-disable import/no-extraneous-dependencies */

import { icons } from 'ckeditor5/src/core';
import {createDropdown} from 'ckeditor5/src/ui';
import MediaFormView from "../mediaembed/ui/mediaformview";
import {getFormValidators} from "../mediaembed/mediaembedui";
import twitterEmbedIcon from "../../../../icons/xembed.svg";
import {MediaEmbedUI} from "../mediaembed";
import TwitterEmbedEditing from "./twitterembedediting";

/**
 * The twitter UI plugin.
 *
 * @private
 */
export default class TwitterEmbedUI extends MediaEmbedUI {

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ TwitterEmbedEditing ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'TwitterEmbedUI';
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const command = editor.commands.get( 'twitterEmbed' );
    const registry = editor.plugins.get( TwitterEmbedEditing ).registry;

    editor.ui.componentFactory.add( 'twitterEmbed', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'tweet', twitterEmbedIcon, 'Insert Tweet' );
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );

    editor.ui.componentFactory.add( 'twitterEdit', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'tweet', icons.pencil, 'Edit Tweet Url' );
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );
  }

}
