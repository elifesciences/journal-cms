/**
 * @module googlemap-embed/googlemapembedtoolbar
 */

import { WidgetToolbarRepository, isWidget } from 'ckeditor5/src/widget';
import {MediaEmbedToolbar} from "../mediaembed";

/**
 * The google map toolbar plugin. It creates a toolbar for google map
 * that shows up when the google map element is selected.
 */
export default class GoogleMapEmbedToolbar extends MediaEmbedToolbar {

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'GoogleMapEmbedToolbar';
  }

  /**
   * @inheritDoc
   */
  afterInit() {
    const editor = this.editor;
    const t = editor.t;
    const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

    widgetToolbarRepository.register( 'googleMapEmbed', {
      ariaLabel: t( 'Google Map embed toolbar' ),
      items: editor.config.get( 'googleMapEmbed.toolbar' ) || [],
      //getRelatedElement: getSelectedMediaViewWidget
      getRelatedElement: (selection) => {
        const viewElement = selection.getSelectedElement();
        if (viewElement && isWidget( viewElement ) &&
          !!viewElement.getCustomProperty( 'media' )
          && viewElement.getCustomProperty('media-type' ) === 'gmap') {
          return viewElement;
        }
        return null;
      }
    });
  }
}
