/**
 * @module media-caption/mediacaption
 */

import { Plugin } from 'ckeditor5/src/core';
import MediaCaptionEditing from './mediacaptionediting';
import MediaCaptionUI from './mediacaptionui';

/**
 * Provides the media feature on media embed elements.
 *
 * @private
 */
export default class MediaCaption extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [MediaCaptionEditing, MediaCaptionUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'MediaCaption';
  }
}
