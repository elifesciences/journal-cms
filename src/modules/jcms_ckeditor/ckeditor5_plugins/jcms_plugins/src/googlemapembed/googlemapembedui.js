/**
 * @module googlemap-embed/googlemapembedui
 */

/* eslint-disable import/no-extraneous-dependencies */
// cSpell:words linkui

import { icons } from 'ckeditor5/src/core';
import {createDropdown} from 'ckeditor5/src/ui';
import MediaFormView from "../mediaembed/ui/mediaformview";
import {getFormValidators} from "../mediaembed/mediaembedui";
import mapEmbedIcon from "../../../../icons/mapembed.svg";
import {MediaEmbedUI} from "../mediaembed";
import GoogleMapEmbedEditing from "./googlemapembedediting";

/**
 * The google map UI plugin.
 *
 * @private
 */
export default class GoogleMapEmbedUI extends MediaEmbedUI {

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ GoogleMapEmbedEditing ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'GoogleMapEmbedUI';
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const command = editor.commands.get( 'googleMapEmbed' );
    const registry = editor.plugins.get( GoogleMapEmbedEditing ).registry;

    editor.ui.componentFactory.add( 'googleMapEmbed', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'gmap', mapEmbedIcon, 'Insert Google Map' );
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );

    editor.ui.componentFactory.add( 'googleMapEdit', locale => {
      const dropdown = createDropdown( locale );
      const mediaForm = new MediaFormView( getFormValidators( editor.t, registry ), editor.locale );

      this._setUpDropdown( dropdown, mediaForm, command, 'gmap', icons.pencil, 'Edit Google Map Url' );
      this._setUpForm( dropdown, mediaForm, command );

      return dropdown;
    } );
  }

}
