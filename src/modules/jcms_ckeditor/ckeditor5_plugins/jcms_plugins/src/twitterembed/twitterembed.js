/**
 * @module twitter-embed/twitterembed
 */

/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupallinkmediaediting drupallinkmediaui */

import { Plugin } from 'ckeditor5/src/core';
import { Widget } from 'ckeditor5/src/widget';
import TwitterEmbedEditing from './twitterembedediting';
import TwitterEmbedUI from './twitterembedui';

/**
 * @private
 */
export default class TwitterEmbed extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [TwitterEmbedEditing, TwitterEmbedUI, Widget];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'TwitterEmbed';
  }
}
