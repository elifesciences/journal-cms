
import { Plugin } from 'ckeditor5/src/core';

/**
 * The media caption utilities plugin.
 */
export default class MediaCaptionUtils extends Plugin {
  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'MediaCaptionUtils';
  }

  /**
   * @inheritDoc
   */
  static get requires() {
    return [];
  }

  isMediaView( element ) {
    return !!element && element.is( 'element', 'figure' );
  }

  isMedia( modelElement ) {
    return !!modelElement && modelElement.is( 'element', 'media' );
  }

  /**
   * Returns the caption model element from a given media element. Returns `null` if no caption is found.
   */
  getCaptionFromMediaModelElement( mediaModelElement ) {
    for ( const node of mediaModelElement.getChildren() ) {
      if ( !!node && node.is( 'element', 'caption' ) ) {
        return node;
      }
    }

    return null;
  }

  /**
   * Returns the caption model element for a model selection. Returns `null` if the selection has no caption element ancestor.
   */
  getCaptionFromModelSelection( selection ) {
    const captionElement = selection.getFirstPosition().findAncestor( 'caption' );

    if ( !captionElement ) {
      return null;
    }

    if ( this.isMedia( captionElement.parent ) ) {
      return captionElement;
    }

    return null;
  }

  /**
   * {@link module:engine/view/matcher~Matcher} pattern. Checks if a given element is a `<figcaption>` element that is placed
   * inside the media `<figure>` element.
   *
   * @param {module:engine/view/element~Element} element
   * @returns {Object|null} Returns the object accepted by {@link module:engine/view/matcher~Matcher} or `null` if the element
   * cannot be matched.
   */
  matchMediaCaptionViewElement( element ) {
    // Convert only captions for media.
    if ( element.name == 'figcaption' && this.isMediaView( element.parent ) ) {
      return { name: true };
    }

    return null;
  }
}
