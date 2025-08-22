/**
 * @module captioned-video/captionedvideo
 */

/* eslint-disable import/no-extraneous-dependencies */

import { Plugin } from 'ckeditor5/src/core';
import { Widget } from 'ckeditor5/src/widget';
import CaptionedVideoEditing from './captionedvideoediting';
import CaptionedVideoUI from './captionedvideoui';

/**
 * @private
 */
export default class CaptionedVideo extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [CaptionedVideoEditing, CaptionedVideoUI, Widget];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'CaptionedVideo';
  }
}
