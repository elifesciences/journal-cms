/**
 * @module figshare-embed/figshareembed
 */

/* eslint-disable import/no-extraneous-dependencies */

import { Plugin } from 'ckeditor5/src/core';
import { Widget } from 'ckeditor5/src/widget';
import FigshareEmbedEditing from './figshareembedediting';
import FigshareEmbedUI from './figshareembedui';

/**
 * @private
 */
export default class FigshareEmbed extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [FigshareEmbedEditing, FigshareEmbedUI, Widget];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'FigshareEmbed';
  }
}
