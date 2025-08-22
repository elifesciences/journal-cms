/**
 * @module media-caption/mediacaptionedediting
 */

import { Plugin } from 'ckeditor5/src/core';
import { Element, enablePlaceholder } from 'ckeditor5/src/engine';
import { toWidgetEditable } from 'ckeditor5/src/widget';
import ToggleMediaCaptionCommand from './togglemediacaptioncommand';
import MediaCaptionUtils from './mediacaptionutils';

/**
 * The media caption plugin. It is responsible for:
 *
 * registering converters for the caption element,
 * registering converters for the caption model attribute,
 *
 * @extends module:core/plugin~Plugin
 */
export default class MediaCaptionEditing extends Plugin {

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ MediaCaptionUtils ];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'MediaCaptionEditing';
  }

  /**
   * @inheritDoc
   */
  constructor( editor ) {
    super( editor );

    /**
     * A map that keeps saved JSONified media captions and media model elements they are
     * associated with.
     *
     * To learn more about this system, see {@link #_saveCaption}.
     *
     * @member {WeakMap.<module:engine/model/element~Element,Object>}
     */
    this._savedCaptionsMap = new WeakMap();
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const schema = editor.model.schema;

    // Schema configuration.
    if ( !schema.isRegistered( 'caption' ) ) {
      schema.register( 'caption', {
        allowIn: 'media',
        allowContentOf: '$block',
        isLimit: true
      } );
    } else {
      schema.extend( 'caption', {
        allowIn: 'media'
      } );
    }

    // Disallow inline images to force image block.
    schema.addChildCheck( ( context, childDefinition ) => {
      if ( childDefinition.name == 'imageInline' ) {
        return false;
      }
    } );

    editor.commands.add( 'toggleMediaCaption', new ToggleMediaCaptionCommand( this.editor ) );

    this._setupConversion();
  }

  /**
   * Configures conversion pipelines to support upcasting and downcasting
   * media captions.
   *
   * @private
   */
  _setupConversion() {
    const editor = this.editor;
    const view = editor.editing.view;
    const mediaCaptionUtils = editor.plugins.get( 'MediaCaptionUtils' );
    const t = editor.t;

    // View -> model converter for the data pipeline.
    editor.conversion.for( 'upcast' ).elementToElement( {
      view: element => mediaCaptionUtils.matchMediaCaptionViewElement( element ),
      model: ( viewMedia, { writer } ) => {
        return writer.createElement( 'caption' );
      }
    } );

    // Model -> view converter for the data pipeline.
    editor.conversion.for( 'dataDowncast' ).elementToElement( {
      model: 'caption',
      view: ( modelElement, { writer } ) => {
        if ( !mediaCaptionUtils.isMedia( modelElement.parent ) ) {
          return null;
        }

        return writer.createContainerElement( 'figcaption' );
      }
    } );

    // Model -> view converter for the editing pipeline.
    editor.conversion.for( 'editingDowncast' ).elementToElement( {
      model: 'caption',
      view: ( modelElement, { writer } ) => {
        if ( !mediaCaptionUtils.isMedia( modelElement.parent ) ) {
          return null;
        }

        const figcaptionElement = writer.createEditableElement( 'figcaption' );
        writer.setCustomProperty( 'mediaCaption', true, figcaptionElement );

        enablePlaceholder( {
          view,
          element: figcaptionElement,
          text: t( 'Enter media caption' ),
          keepOnFocus: true
        } );

        const label = t( 'Caption for the media' );

        return toWidgetEditable( figcaptionElement, writer, { label } );
      }
    } );
  }

  /**
   * Returns the saved {@link module:engine/model/element~Element#toJSON JSONified} caption
   * of an media model element.
   *
   * See {@link #_saveCaption}.
   *
   * @protected
   * @param {module:engine/model/element~Element} mediaModelElement The model element the
   * caption should be returned for.
   * @returns {module:engine/model/element~Element|null} The model caption element or `null` if there is none.
   */
  _getSavedCaption( mediaModelElement ) {
    const jsonObject = this._savedCaptionsMap.get( mediaModelElement );

    return jsonObject ? Element.fromJSON( jsonObject ) : null;
  }

  /**
   * Saves a {@link module:engine/model/element~Element#toJSON JSONified} caption for
   * an media element to allow restoring it in the future.
   *
   * A caption is saved every time it gets hidden. The
   * user should be able to restore it on demand.
   *
   * See {@link #_getSavedCaption}.
   *
   * @protected
   * @param {module:engine/model/element~Element} mediaModelElement The model element the
   * caption is saved for.
   * @param {module:engine/model/element~Element} caption The caption model element to be saved.
   */
  _saveCaption( mediaModelElement, caption ) {
    this._savedCaptionsMap.set( mediaModelElement, caption.toJSON() );
  }
}
