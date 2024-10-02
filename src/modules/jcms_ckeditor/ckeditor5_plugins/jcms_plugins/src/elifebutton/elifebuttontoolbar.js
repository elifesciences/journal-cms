/**
 * @module elife-button/elifebuttontoolbar
 */

import { Plugin } from 'ckeditor5/src/core';
import { WidgetToolbarRepository, isWidget } from 'ckeditor5/src/widget';

/**
 * The elife button toolbar plugin. It creates a toolbar for elife button
 * that shows up when the elife button element is selected.
 */
export default class ElifeButtonToolbar extends Plugin {

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ WidgetToolbarRepository ];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'ElifeButtonToolbar';
  }

  /**
   * @inheritDoc
   */
  afterInit() {
    const editor = this.editor;
    const t = editor.t;
    const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

    widgetToolbarRepository.register( 'elifeButton', {
      ariaLabel: t( 'Elife Button toolbar' ),
      items: editor.config.get( 'elifeButton.toolbar' ) || [],
      getRelatedElement: (selection) => {
        const viewElement = selection.getSelectedElement();
        if (viewElement && isWidget( viewElement ) &&
          !!viewElement.getCustomProperty( 'elifebutton' )) {
          return viewElement;
        }
        return null;
      }
    });
  }
}
