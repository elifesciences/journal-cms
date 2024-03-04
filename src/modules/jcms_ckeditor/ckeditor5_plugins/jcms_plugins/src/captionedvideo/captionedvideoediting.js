/**
 * @module captioned-video/captionedvideoediting
 */

/* eslint-disable import/no-extraneous-dependencies */

import CaptionedVideoCommand from "./captionedvideocommand";
import { MediaEmbedEditing } from "../mediaembed";

/**
 * Model to view and view to model conversions for captioned video media.
 *
 * @private
 *
 */
export default class CaptionedVideoEditing extends MediaEmbedEditing {

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'CaptionedVideoEditing';
  }

  /**
   * @inheritDoc
   */
  constructor( editor ) {
    super(editor);
    this.setupConfig(editor, 'captionedVideo');
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;
    editor.commands.add( 'captionedVideo', new CaptionedVideoCommand( editor ) );
    this.initSchema('captionedVideo', 'video');
  }

}
