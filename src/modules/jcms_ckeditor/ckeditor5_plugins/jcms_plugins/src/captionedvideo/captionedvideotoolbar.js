/**
 * @module captioned-video/captionedvideotoolbar
 */

import { WidgetToolbarRepository, isWidget } from 'ckeditor5/src/widget';
import {MediaEmbedToolbar} from "../mediaembed";

/**
 * The captioned video toolbar plugin. It creates a toolbar for captioned video
 * that shows up when the captioned video element is selected.
 */
export default class CaptionedVideoToolbar extends MediaEmbedToolbar {

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'CaptionedVideoToolbar';
  }

  /**
   * @inheritDoc
   */
  afterInit() {
    const editor = this.editor;
    const t = editor.t;
    const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

    widgetToolbarRepository.register( 'captionedVideo', {
      ariaLabel: t( 'Captioned Video toolbar' ),
      items: editor.config.get( 'captionedVideo.toolbar' ) || [],
      //getRelatedElement: getSelectedMediaViewWidget
      getRelatedElement: (selection) => {
        const viewElement = selection.getSelectedElement();
        if (viewElement && isWidget( viewElement ) &&
          !!viewElement.getCustomProperty( 'media' ) &&
          viewElement.getCustomProperty('media-type' ) === 'video') {
          return viewElement;
        }
        return null;
      }
    });
  }
}
