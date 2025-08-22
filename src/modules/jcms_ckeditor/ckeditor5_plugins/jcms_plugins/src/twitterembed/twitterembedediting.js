/**
 * @module twitter-embed/twitterembedediting
 */

/* eslint-disable import/no-extraneous-dependencies */

import TwitterEmbedCommand from "./twitterembedcommand";
import { MediaEmbedEditing } from "../mediaembed";

/**
 * Model to view and view to model conversions for linked media elements.
 *
 * @private
 *
 * @see https://github.com/ckeditor/ckeditor5/blob/v31.0.0/packages/ckeditor5-link/src/linkimage.js
 */
export default class TwitterEmbedEditing extends MediaEmbedEditing {

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'twitterEmbedEditing';
  }

  /**
   * @inheritDoc
   */
  constructor( editor ) {
    super(editor);
    this.setupConfig(editor, 'twitterEmbed');
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;
    editor.commands.add( 'twitterEmbed', new TwitterEmbedCommand( editor ) );
    this.initSchema('twitterEmbed', 'tweet');
  }

}
