/**
 * @module figshare-embed/figshareembedediting
 */

/* eslint-disable import/no-extraneous-dependencies */

import FigshareEmbedCommand from "./figshareembedcommand";
import { MediaEmbedEditing } from "../mediaembed";

/**
 * Model to view and view to model conversions for figshare media elements.
 *
 * @private
 *
 */
export default class FigshareEmbedEditing extends MediaEmbedEditing {

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'FigshareEmbedEditing';
  }

  /**
   * @inheritDoc
   */
  constructor( editor ) {
    super(editor);
    this.setupConfig(editor, 'figshareEmbed');
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;
    editor.commands.add( 'figshareEmbed', new FigshareEmbedCommand( editor ) );
    this.initSchema('figshareEmbed', 'figshare');
  }

}
