/**
 * @module twitter-embed/twitterembedtoolbar
 */

import { WidgetToolbarRepository, isWidget } from 'ckeditor5/src/widget';
import {MediaEmbedToolbar} from "../mediaembed";

/**
 * The twitter toolbar plugin. It creates a toolbar for twitter
 * that shows up when the twitter element is selected.
 */
export default class TwitterEmbedToolbar extends MediaEmbedToolbar {

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'TwitterEmbedToolbar';
  }

  /**
   * @inheritDoc
   */
  afterInit() {
    const editor = this.editor;
    const t = editor.t;
    const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

    widgetToolbarRepository.register( 'twitterEmbed', {
      ariaLabel: t( 'Twitter embed toolbar' ),
      items: editor.config.get( 'twitterEmbed.toolbar' ) || [],
      //getRelatedElement: getSelectedMediaViewWidget
      getRelatedElement: (selection) => {
        const viewElement = selection.getSelectedElement();
        if (viewElement && isWidget( viewElement ) &&
          !!viewElement.getCustomProperty( 'media' ) &&
          viewElement.getCustomProperty('media-type' ) === 'tweet') {
          return viewElement;
        }
        return null;
      }
    });
  }
}
