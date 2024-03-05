/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module media-embed/mediaembedediting
 */

import { Plugin } from 'ckeditor5/src/core';

import { modelToViewUrlAttributeConverter } from './converters';
import MediaEmbedCommand from './mediaembedcommand';
import MediaRegistry from './mediaregistry';
import { toMediaWidget, createMediaFigureElement } from './utils';
import './theme/mediaembedediting.css';
import mapEmbedIcon from "../../../../icons/mapembed.svg";
import twitterEmbedIcon from "../../../../icons/xembed.svg";

/**
 * The media embed editing feature.
 *
 * @extends module:core/plugin~Plugin
 */
export default class MediaEmbedEditing extends Plugin {
	/**
	 * @inheritDoc
	 */
	static get pluginName() {
		return 'MediaEmbedEditing';
	}

	/**
	 * @inheritDoc
	 */
	constructor( editor ) {
    super(editor);
    this.setupConfig(editor, 'mediaEmbed');
  }

  /**
   * Setup inital config for this plugin
   *
   * @param {Object} editor The editor from the constructor
   * @param {String} configName The base config name
   */
  setupConfig(editor, configName) {
		editor.config.define( configName, {
			elementName: 'oembed',
			providers: [
				{
					name: 'dailymotion',
					url: /^dailymotion\.com\/video\/(\w+)/,
					html: match => {
						const id = match[ 1 ];

						return (
							'<div style="position: relative; padding-bottom: 100%; height: 0; ">' +
								`<iframe src="https://www.dailymotion.com/embed/video/${ id }" ` +
									'style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" ' +
									'frameborder="0" width="480" height="270" allowfullscreen allow="autoplay">' +
								'</iframe>' +
							'</div>'
						);
					}
				},

				{
					name: 'spotify',
					url: [
						/^open\.spotify\.com\/(artist\/\w+)/,
						/^open\.spotify\.com\/(album\/\w+)/,
						/^open\.spotify\.com\/(track\/\w+)/
					],
					html: match => {
						const id = match[ 1 ];

						return (
							'<div style="position: relative; padding-bottom: 100%; height: 0; padding-bottom: 126%;">' +
								`<iframe src="https://open.spotify.com/embed/${ id }" ` +
									'style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" ' +
									'frameborder="0" allowtransparency="true" allow="encrypted-media">' +
								'</iframe>' +
							'</div>'
						);
					}
				},

				{
					name: 'youtube',
					url: [
						/^(?:m\.)?youtube\.com\/watch\?v=([\w-]+)(?:&t=(\d+))?/,
						/^(?:m\.)?youtube\.com\/v\/([\w-]+)(?:\?t=(\d+))?/,
						/^youtube\.com\/embed\/([\w-]+)(?:\?start=(\d+))?/,
						/^youtu\.be\/([\w-]+)(?:\?t=(\d+))?/
					],
					html: match => {
						const id = match[ 1 ];
						const time = match[ 2 ];

						return (
							'<div style="position: relative; padding-bottom: 100%; height: 0; padding-bottom: 56.2493%;">' +
								`<iframe src="https://www.youtube.com/embed/${ id }${ time ? `?start=${ time }` : '' }" ` +
									'style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" ' +
									'frameborder="0" allow="autoplay; encrypted-media" allowfullscreen>' +
								'</iframe>' +
							'</div>'
						);
					}
				},

				{
					name: 'vimeo',
					url: [
						/^vimeo\.com\/(\d+)/,
						/^vimeo\.com\/[^/]+\/[^/]+\/video\/(\d+)/,
						/^vimeo\.com\/album\/[^/]+\/video\/(\d+)/,
						/^vimeo\.com\/channels\/[^/]+\/(\d+)/,
						/^vimeo\.com\/groups\/[^/]+\/videos\/(\d+)/,
						/^vimeo\.com\/ondemand\/[^/]+\/(\d+)/,
						/^player\.vimeo\.com\/video\/(\d+)/
					],
					html: match => {
						const id = match[ 1 ];

						return (
							'<div style="position: relative; padding-bottom: 100%; height: 0; padding-bottom: 56.2493%;">' +
								`<iframe src="https://player.vimeo.com/video/${ id }" ` +
									'style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" ' +
									'frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen>' +
								'</iframe>' +
							'</div>'
						);
					}
				},

				{
					name: 'instagram',
					url: /^instagram\.com\/p\/(\w+)/
				},
				{
					name: 'twitter',
					url: [
            /^twitter\.com\/(\w+)/,
            /^x\.com\/(\w+)/,
          ],
          html: match => {
            const src = "https://www." + match.input;
            return (
              '<div class="ck ck-reset_all ck-media__placeholder">' +
              '<div class="ck-media__placeholder__icon">' + twitterEmbedIcon + '</div>' +
              `<a class="ck-media__placeholder__url" target="_blank" data-cke-tooltip-text="Open media in new tab" href="${ src }">` +
              '<span class="ck-media__placeholder__url__text">' + src +
              '</span></a></div>'
            );
          }
				},
				{
					name: 'googleMaps',
					url: [
						/^google\.com\/maps\/(\w+)/,
						/^goo\.gl\/maps\/(\w+)/,
						/^maps\.google\.com\/(\w+)/,
						/^maps\.app\.goo\.gl\/(\w+)/
					],
          html: match => {
            const src = "https://www." + match.input;
            return (
              '<div class="ck ck-reset_all ck-media__placeholder">' +
              '<div class="ck-media__placeholder__icon">' + mapEmbedIcon + '</div>' +
              `<a class="ck-media__placeholder__url" target="_blank" data-cke-tooltip-text="Open media in new tab" href="${ src }">` +
              '<span class="ck-media__placeholder__url__text">' + src +
              '</span></a></div>'
            );
          }
				},
				{
					name: 'flickr',
					url: /^flickr\.com/
				},
				{
					name: 'facebook',
					url: /^facebook\.com/
				},
        {
          name: 'figshare',
          url: /^widgets.figshare\.com\/(\w+)/,
          html: match => {
            return (
              '<div style="position: relative; padding-bottom: 75%; height: 0; padding-bottom: 56.2493%;">' +
              `<iframe src="https://${ match.input }" ` +
              'style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" ' +
              'frameborder="0" allowfullscreen>' +
              '</iframe>' +
              '</div>'
            );
          }
        }
			]
		} );

		/**
		 * The media registry managing the media providers in the editor.
		 *
		 * @member {module:media-embed/mediaregistry~MediaRegistry} #registry
		 */
		this.registry = new MediaRegistry( editor.locale, editor.config.get( configName ) );
	}

	/**
	 * @inheritDoc
	 */
	init() {
		const editor = this.editor;
		editor.commands.add( 'mediaEmbed', new MediaEmbedCommand( editor ) );
    this.initSchema('mediaEmbed', 'media');
	}

  /**
   * Configure the schema
   *
   * @param {String} configName The base config name
   * @param {String} type The type of media
   */
  initSchema(configName, type) {
    const editor = this.editor;
    const t = editor.t;
    const schema = editor.model.schema;

    if ( !schema.isRegistered( 'media' ) ) {
      schema.register('media', {
        inheritAllFrom: '$blockObject',
        allowAttributes: ['url', 'type']
      });
    }

    const registry = this.registry;
    const conversion = editor.conversion;
    const renderMediaPreview = editor.config.get( configName + '.previewsInData' );
    const elementName = editor.config.get( configName + '.elementName' );

    // Model -> Data
    conversion.for( 'dataDowncast' ).elementToStructure( {
      model: 'media',
      view: ( modelElement, { writer } ) => {
        const url = modelElement.getAttribute( 'url' );
        const mediaType = modelElement.getAttribute( 'type' );
        if (mediaType === type) {
          return createMediaFigureElement(writer, registry, url, mediaType, {
            elementName,
            renderMediaPreview: url && mediaType && renderMediaPreview
          });
        }
      }
    } );

    // Model -> Data (url -> data-oembed-url)
    conversion.for( 'dataDowncast' ).add(
      modelToViewUrlAttributeConverter( registry, type,{
        elementName,
        renderMediaPreview
      } ) );

    // Model -> View (element)
    conversion.for( 'editingDowncast' ).elementToStructure( {
      model: 'media',
      view: ( modelElement, { writer } ) => {
        const url = modelElement.getAttribute( 'url' );
        const mediaType = modelElement.getAttribute( 'type' );
        if (mediaType === type) {
          const figure = createMediaFigureElement(writer, registry, url, mediaType, {
            elementName,
            renderForEditingView: true
          });
          writer.setCustomProperty('media-type', mediaType, figure)
          return toMediaWidget(figure, writer, t('media widget'));
        }
      }
    } );

    // Model -> View (url -> data-oembed-url)
    conversion.for( 'editingDowncast' ).add(
      modelToViewUrlAttributeConverter( registry, type,{
        elementName,
        renderForEditingView: true
      } ) );

    // View -> Model (data-oembed-url -> url)
    conversion.for( 'upcast' ).add( dispatcher => {
        dispatcher.on('element:figure', converter);

        function converter(evt, data, conversionApi) {
          // Get all the necessary items from the conversion API object.
          const {
            consumable,
            writer,
            safeInsert,
            convertChildren,
            updateConversionResult
          } = conversionApi;

          // Get view item from data object.
          const { viewItem } = data;

          if (!consumable.test(viewItem, {name: 'figure'})) {
            return;
          }

          const classes = viewItem.getClassNames();
          const type = classes.next().value;

          // Get the first child element.
          const firstChildItem = viewItem.getChild( 0 );

          // Check if the first element is a <oembed>.
          if ( !firstChildItem.is( 'element', 'oembed' ) &&
            !firstChildItem.is( 'element', 'iframe' )) {
            return;
          }
          if ( !consumable.test( firstChildItem, {name: 'oembed'} ) &&
            !consumable.test( firstChildItem, {name: 'iframe'} )) {
            return;
          }

          const attrName = firstChildItem.is( 'element', 'iframe' ) ? 'src' : 'url';
          // Create model media element.
          let url = firstChildItem.getAttribute( attrName );
          if (!url) {
            url = firstChildItem.getCustomProperty('$rawContent');
          }
          const modelElement = writer.createElement( 'media', { url: url, type: type } );

          // Insert element on a current cursor location.
          if ( !safeInsert( modelElement, data.modelCursor ) ) {
            return;
          }
          consumable.consume(viewItem, {name: 'figure'})

          convertChildren( viewItem, modelElement );
          updateConversionResult( modelElement, data );
        }
      });
  }
}
