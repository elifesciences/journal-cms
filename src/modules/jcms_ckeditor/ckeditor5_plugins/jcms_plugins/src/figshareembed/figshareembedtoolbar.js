/**
 * @module figshare-embed/figshareembedtoolbar
 */

import { WidgetToolbarRepository, isWidget } from 'ckeditor5/src/widget';
import {MediaEmbedToolbar} from "../mediaembed";

/**
 * The figshare toolbar plugin. It creates a toolbar for figshare media
 * that shows up when the figshare element is selected.
 */
export default class FigshareEmbedToolbar extends MediaEmbedToolbar {

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'FigshareEmbedToolbar';
  }

  /**
   * @inheritDoc
   */
  afterInit() {
    const editor = this.editor;
    const t = editor.t;
    const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

    widgetToolbarRepository.register( 'figshareEmbed', {
      ariaLabel: t( 'Figsahre embed toolbar' ),
      items: editor.config.get( 'figshareEmbed.toolbar' ) || [],
      //getRelatedElement: getSelectedMediaViewWidget
      getRelatedElement: (selection) => {
        const viewElement = selection.getSelectedElement();
        if (viewElement && isWidget( viewElement ) &&
          !!viewElement.getCustomProperty( 'media' ) &&
          viewElement.getCustomProperty('media-type' ) === 'figshare') {
          return viewElement;
        }
        return null;
      }
    });
  }
}
