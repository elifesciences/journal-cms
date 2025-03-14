/**
 * @module googlemap-embed/googlemapembedediting
 */

/* eslint-disable import/no-extraneous-dependencies */

import GoogleMapEmbedCommand from "./googlemapembedcommand";
import { MediaEmbedEditing } from "../mediaembed";

/**
 * Model to view and view to model conversions for linked media elements.
 *
 * @private
 *
 */
export default class GoogleMapEmbedEditing extends MediaEmbedEditing {

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'GoogleMapEmbedEditing';
  }

  /**
   * @inheritDoc
   */
  constructor( editor ) {
    super(editor);
    this.setupConfig(editor, 'googleMapEmbed');
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;
    editor.commands.add( 'googleMapEmbed', new GoogleMapEmbedCommand( editor ) );
    this.initSchema('googleMapEmbed', 'gmap');
  }

}
