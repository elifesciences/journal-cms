/**
 * @module googlemap-embed/googlemapembed
 */

/* eslint-disable import/no-extraneous-dependencies */

import { Plugin } from 'ckeditor5/src/core';
import { Widget } from 'ckeditor5/src/widget';
import GoogleMapEmbedEditing from './googlemapembedediting';
import GoogleMapEmbedUI from './googlemapembedui';

/**
 * @private
 */
export default class GoogleMapEmbed extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [GoogleMapEmbedEditing, GoogleMapEmbedUI, Widget];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'GoogleMapEmbed';
  }
}
