(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["CKEditor5"] = factory();
	else
		root["CKEditor5"] = root["CKEditor5"] || {}, root["CKEditor5"]["drupalImage"] = factory();
})(self, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "ckeditor5/src/core.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = (__webpack_require__("dll-reference CKEditor5.dll"))("./src/core.js");

/***/ }),

/***/ "ckeditor5/src/ui.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = (__webpack_require__("dll-reference CKEditor5.dll"))("./src/ui.js");

/***/ }),

/***/ "ckeditor5/src/upload.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = (__webpack_require__("dll-reference CKEditor5.dll"))("./src/upload.js");

/***/ }),

/***/ "ckeditor5/src/utils.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = (__webpack_require__("dll-reference CKEditor5.dll"))("./src/utils.js");

/***/ }),

/***/ "dll-reference CKEditor5.dll":
/***/ ((module) => {

"use strict";
module.exports = CKEditor5.dll;

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  "default": () => (/* binding */ src)
});

// EXTERNAL MODULE: delegated ./core.js from dll-reference CKEditor5.dll
var delegated_corefrom_dll_reference_CKEditor5 = __webpack_require__("ckeditor5/src/core.js");
;// CONCATENATED MODULE: ./node_modules/@ckeditor/ckeditor5-html-support/src/conversionutils.js
/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module html-support/conversionutils
 */



/**
* Helper function for the downcast converter. Updates attributes on the given view element.
*
* @param {module:engine/view/downcastwriter~DowncastWriter} writer The view writer.
* @param {Object} oldViewAttributes The previous GHS attribute value.
* @param {Object} newViewAttributes The current GHS attribute value.
* @param {module:engine/view/element~Element} viewElement The view element to update.
*/
function updateViewAttributes( writer, oldViewAttributes, newViewAttributes, viewElement ) {
	if ( oldViewAttributes ) {
		removeViewAttributes( writer, oldViewAttributes, viewElement );
	}

	if ( newViewAttributes ) {
		setViewAttributes( writer, newViewAttributes, viewElement );
	}
}

/**
 * Helper function for the downcast converter. Sets attributes on the given view element.
 *
 * @param {module:engine/view/downcastwriter~DowncastWriter} writer The view writer.
 * @param {Object} viewAttributes The GHS attribute value.
 * @param {module:engine/view/element~Element} viewElement The view element to update.
 */
function setViewAttributes( writer, viewAttributes, viewElement ) {
	if ( viewAttributes.attributes ) {
		for ( const [ key, value ] of Object.entries( viewAttributes.attributes ) ) {
			writer.setAttribute( key, value, viewElement );
		}
	}

	if ( viewAttributes.styles ) {
		writer.setStyle( viewAttributes.styles, viewElement );
	}

	if ( viewAttributes.classes ) {
		writer.addClass( viewAttributes.classes, viewElement );
	}
}

/**
 * Helper function for the downcast converter. Removes attributes on the given view element.
 *
 * @param {module:engine/view/downcastwriter~DowncastWriter} writer The view writer.
 * @param {Object} viewAttributes The GHS attribute value.
 * @param {module:engine/view/element~Element} viewElement The view element to update.
 */
function removeViewAttributes( writer, viewAttributes, viewElement ) {
	if ( viewAttributes.attributes ) {
		for ( const [ key ] of Object.entries( viewAttributes.attributes ) ) {
			writer.removeAttribute( key, viewElement );
		}
	}

	if ( viewAttributes.styles ) {
		for ( const style of Object.keys( viewAttributes.styles ) ) {
			writer.removeStyle( style, viewElement );
		}
	}

	if ( viewAttributes.classes ) {
		writer.removeClass( viewAttributes.classes, viewElement );
	}
}

/**
* Merges view element attribute objects.
*
* @param {Object} target
* @param {Object} source
* @returns {Object}
*/
function mergeViewElementAttributes( target, source ) {
	const result = cloneDeep( target );

	for ( const key in source ) {
		// Merge classes.
		if ( Array.isArray( source[ key ] ) ) {
			result[ key ] = Array.from( new Set( [ ...( target[ key ] || [] ), ...source[ key ] ] ) );
		}

		// Merge attributes or styles.
		else {
			result[ key ] = { ...target[ key ], ...source[ key ] };
		}
	}

	return result;
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/drupalimageediting.js
/* eslint-disable import/no-extraneous-dependencies */
// cSpell:words conversionutils downcasted linkimageediting emptyelement downcastdispatcher



/**
 * @typedef {function} converterHandler
 *
 * Callback for a CKEditor 5 event.
 *
 * @param {Event} event
 *  The CKEditor 5 event object.
 * @param {object} data
 *  The data associated with the event.
 * @param {module:engine/conversion/downcastdispatcher~DowncastConversionApi} conversionApi
 *  The CKEditor 5 conversion API object.
 */

/**
 * Provides an empty image element.
 *
 * @param {writer} writer
 *  The CKEditor 5 writer object.
 *
 * @return {module:engine/view/emptyelement~EmptyElement}
 *  The empty image element.
 *
 * @private
 */
function createImageViewElement(writer) {
  return writer.createEmptyElement('img');
}

/**
 * A simple helper method to detect number strings.
 *
 * @param {*} value
 *  The value to test.
 *
 * @return {boolean}
 *  True if the value is a string containing a number.
 *
 * @private
 */
function isNumberString(value) {
  const parsedValue = parseFloat(value);

  return !Number.isNaN(parsedValue) && value === String(parsedValue);
}

/**
 * Generates a callback that saves the entity UUID to an attribute on data
 * downcast.
 *
 * @return {function}
 *  Callback that binds an event to its parameter.
 *
 * @private
 */
function modelEntityUuidToDataAttribute() {
  /**
   * Callback for the attribute:dataEntityUuid event.
   *
   * Saves the UUID value to the data-entity-uuid attribute.
   *
   * @param {Event} event
   * @param {object} data
   * @param {module:engine/conversion/downcastdispatcher~DowncastConversionApi} conversionApi
   */
  function converter(event, data, conversionApi) {
    const { item } = data;
    const { consumable, writer } = conversionApi;

    if (!consumable.consume(item, event.name)) {
      return;
    }

    const viewElement = conversionApi.mapper.toViewElement(item);
    const imageInFigure = Array.from(viewElement.getChildren()).find(
      (child) => child.name === 'img',
    );

    writer.setAttribute(
      'data-entity-uuid',
      data.attributeNewValue,
      imageInFigure || viewElement,
    );
  }

  return (dispatcher) => {
    dispatcher.on('attribute:dataEntityUuid', converter);
  };
}

/**
 * @type {Array.<{dataValue: string, modelValue: string}>}
 */
const alignmentMapping = [
  {
    modelValue: 'alignCenter',
    dataValue: 'center',
  },
  {
    modelValue: 'alignRight',
    dataValue: 'right',
  },
  {
    modelValue: 'alignLeft',
    dataValue: 'left',
  },
];

/**
 * Downcasts `caption` model to `data-caption` attribute with its content
 * downcasted to plain HTML.
 *
 * This is needed because CKEditor 5 uses the `<caption>` element internally in
 * various places, which differs from Drupal which uses an attribute. For now
 * to support that we have to manually repeat work done in the
 * DowncastDispatcher's private methods.
 *
 * @param {module:core/editor/editor~Editor} editor
 *  The editor instance to use.
 *
 * @return {function}
 *  Callback that binds an event to its parameter.
 *
 * @private
 */
function viewCaptionToCaptionAttribute(editor) {
  return (dispatcher) => {
    dispatcher.on(
      'insert:caption',
      /**
       * @type {converterHandler}
       */
      (event, data, conversionApi) => {
        const { consumable, writer, mapper } = conversionApi;
        const imageUtils = editor.plugins.get('ImageUtils');

        if (
          !imageUtils.isImage(data.item.parent) ||
          !consumable.consume(data.item, 'insert')
        ) {
          return;
        }

        const range = editor.model.createRangeIn(data.item);
        const viewDocumentFragment = writer.createDocumentFragment();

        // Bind caption model element to the detached view document fragment so
        // all content of the caption will be downcasted into that document
        // fragment.
        mapper.bindElements(data.item, viewDocumentFragment);

        // eslint-disable-next-line no-restricted-syntax
        for (const { item } of Array.from(range)) {
          const itemData = {
            item,
            range: editor.model.createRangeOn(item),
          };

          // The following lines are extracted from
          // DowncastDispatcher._convertInsertWithAttributes().
          const eventName = `insert:${item.name || '$text'}`;

          editor.data.downcastDispatcher.fire(
            eventName,
            itemData,
            conversionApi,
          );

          // eslint-disable-next-line no-restricted-syntax
          for (const key of item.getAttributeKeys()) {
            Object.assign(itemData, {
              attributeKey: key,
              attributeOldValue: null,
              attributeNewValue: itemData.item.getAttribute(key),
            });

            editor.data.downcastDispatcher.fire(
              `attribute:${key}`,
              itemData,
              conversionApi,
            );
          }
        }

        // Unbind all the view elements that were downcasted to the document
        // fragment.
        // eslint-disable-next-line no-restricted-syntax
        for (const child of writer
          .createRangeIn(viewDocumentFragment)
          .getItems()) {
          mapper.unbindViewElement(child);
        }

        mapper.unbindViewElement(viewDocumentFragment);

        // Stringify view document fragment to HTML string.
        const captionText = editor.data.processor.toData(viewDocumentFragment);

        if (captionText) {
          const imageViewElement = mapper.toViewElement(data.item.parent);

          writer.setAttribute('data-caption', captionText, imageViewElement);
        }
      },
      // Override default caption converter.
      { priority: 'high' },
    );
  };
}

/**
 * Generates a callback that saves the entity type value to an attribute on
 * data downcast.
 *
 * @return {function}
 *  Callback that binds an event to it's parameter.
 *
 * @private
 */
function modelEntityTypeToDataAttribute() {
  /**
   * Callback for the attribute:dataEntityType event.
   *
   * Saves the UUID value to the data-entity-type attribute.
   *
   * @type {converterHandler}
   */
  function converter(event, data, conversionApi) {
    const { item } = data;
    const { consumable, writer } = conversionApi;

    if (!consumable.consume(item, event.name)) {
      return;
    }

    const viewElement = conversionApi.mapper.toViewElement(item);
    const imageInFigure = Array.from(viewElement.getChildren()).find(
      (child) => child.name === 'img',
    );

    writer.setAttribute(
      'data-entity-type',
      data.attributeNewValue,
      imageInFigure || viewElement,
    );
  }

  return (dispatcher) => {
    dispatcher.on('attribute:dataEntityType', converter);
  };
}

/**
 * Generates a callback that saves the align value to an attribute on
 * data downcast.
 *
 * @return {function}
 *  Callback that binds an event to its parameter.
 *
 * @private
 */
function modelImageStyleToDataAttribute() {
  /**
   * Callback for the attribute:imageStyle event.
   *
   * Saves the alignment value to the data-align attribute.
   *
   * @type {converterHandler}
   */
  function converter(event, data, conversionApi) {
    const { item } = data;
    const { consumable, writer } = conversionApi;

    const mappedAlignment = alignmentMapping.find(
      (value) => value.modelValue === data.attributeNewValue,
    );

    // Consume only for the values that can be converted into data-align.
    if (!mappedAlignment || !consumable.consume(item, event.name)) {
      return;
    }

    const viewElement = conversionApi.mapper.toViewElement(item);
    const imageInFigure = Array.from(viewElement.getChildren()).find(
      (child) => child.name === 'img',
    );

    writer.setAttribute(
      'data-align',
      mappedAlignment.dataValue,
      imageInFigure || viewElement,
    );
  }

  return (dispatcher) => {
    dispatcher.on('attribute:imageStyle', converter, { priority: 'high' });
  };
}

/**
 * Generates a callback that saves the width value to an attribute on
 * data downcast.
 *
 * @return {function}
 *  Callback that binds an event to its parameter.
 *
 * @private
 */
function modelImageWidthToAttribute() {
  /**
   * Callback for the attribute:width event.
   *
   * Saves the width value to the width attribute.
   *
   * @type {converterHandler}
   */
  function converter(event, data, conversionApi) {
    const { item } = data;
    const { consumable, writer } = conversionApi;

    if (!consumable.consume(item, event.name)) {
      return;
    }

    const viewElement = conversionApi.mapper.toViewElement(item);
    const imageInFigure = Array.from(viewElement.getChildren()).find(
      (child) => child.name === 'img',
    );

    writer.setAttribute(
      'width',
      data.attributeNewValue.replace('px', ''),
      imageInFigure || viewElement,
    );
  }

  return (dispatcher) => {
    dispatcher.on('attribute:width:imageInline', converter, {
      priority: 'high',
    });
    dispatcher.on('attribute:width:imageBlock', converter, {
      priority: 'high',
    });
  };
}

/**
 * Generates a callback that saves the height value to an attribute on
 * data downcast.
 *
 * @return {function}
 *  Callback that binds an event to its parameter.
 *
 * @private
 */
function modelImageHeightToAttribute() {
  /**
   * Callback for the attribute:height event.
   *
   * Saves the height value to the height attribute.
   *
   * @type {converterHandler}
   */
  function converter(event, data, conversionApi) {
    const { item } = data;
    const { consumable, writer } = conversionApi;

    if (!consumable.consume(item, event.name)) {
      return;
    }

    const viewElement = conversionApi.mapper.toViewElement(item);
    const imageInFigure = Array.from(viewElement.getChildren()).find(
      (child) => child.name === 'img',
    );

    writer.setAttribute(
      'height',
      data.attributeNewValue.replace('px', ''),
      imageInFigure || viewElement,
    );
  }

  return (dispatcher) => {
    dispatcher.on('attribute:height:imageInline', converter, {
      priority: 'high',
    });
    dispatcher.on('attribute:height:imageBlock', converter, {
      priority: 'high',
    });
  };
}

/**
 * Generates a callback that handles the data downcast for the img element.
 *
 * @return {function}
 *  Callback that binds an event to its parameter.
 *
 * @private
 */
function viewImageToModelImage(editor) {
  /**
   * Callback for the element:img event.
   *
   * Handles the Drupal specific attributes.
   *
   * @type {converterHandler}
   */
  function converter(event, data, conversionApi) {
    const { viewItem } = data;
    const { writer, consumable, safeInsert, updateConversionResult, schema } =
      conversionApi;
    const attributesToConsume = [];

    let image;

    // Not only check if a given `img` view element has been consumed, but also
    // verify it has `src` attribute present.
    if (!consumable.test(viewItem, { name: true, attributes: 'src' })) {
      return;
    }

    const hasDataCaption = consumable.test(viewItem, {
      name: true,
      attributes: 'data-caption',
    });

    // Create image that's allowed in the given context. If the image has a
    // caption, the image must be created as a block image to ensure the caption
    // is not lost on conversion. This is based on the assumption that
    // preserving the image caption is more important to the content creator
    // than preserving the wrapping element that doesn't allow block images.
    if (schema.checkChild(data.modelCursor, 'imageInline') && !hasDataCaption) {
      image = writer.createElement('imageInline', {
        src: viewItem.getAttribute('src'),
      });
    } else {
      image = writer.createElement('imageBlock', {
        src: viewItem.getAttribute('src'),
      });
    }

    // The way that image styles are handled here is naive - it assumes that the
    // image styles are configured exactly as expected by this plugin.
    // @todo Add support for custom image style configurations
    //   https://www.drupal.org/i/3270693.
    if (
      editor.plugins.has('ImageStyleEditing') &&
      consumable.test(viewItem, { name: true, attributes: 'data-align' })
    ) {
      const dataAlign = viewItem.getAttribute('data-align');
      const mappedAlignment = alignmentMapping.find(
        (value) => value.dataValue === dataAlign,
      );

      if (mappedAlignment) {
        writer.setAttribute('imageStyle', mappedAlignment.modelValue, image);

        // Make sure the attribute can be consumed after successful `safeInsert`
        // operation.
        attributesToConsume.push('data-align');
      }
    }

    // Check if the view element has still unconsumed `data-caption` attribute.
    if (hasDataCaption) {
      // Create `caption` model element. Thanks to that element the rest of the
      // `ckeditor5-plugin` converters can recognize this image as a block image
      // with a caption.
      const caption = writer.createElement('caption');

      // Parse HTML from data-caption attribute and upcast it to model fragment.
      const viewFragment = editor.data.processor.toView(
        viewItem.getAttribute('data-caption'),
      );

      // Consumable must know about those newly parsed view elements.
      conversionApi.consumable.constructor.createFrom(
        viewFragment,
        conversionApi.consumable,
      );
      conversionApi.convertChildren(viewFragment, caption);

      // Insert the caption element into image, as a last child.
      writer.append(caption, image);

      // Make sure the attribute can be consumed after successful `safeInsert`
      // operation.
      attributesToConsume.push('data-caption');
    }

    if (
      consumable.test(viewItem, { name: true, attributes: 'data-entity-uuid' })
    ) {
      writer.setAttribute(
        'dataEntityUuid',
        viewItem.getAttribute('data-entity-uuid'),
        image,
      );
      attributesToConsume.push('data-entity-uuid');
    }

    if (
      consumable.test(viewItem, { name: true, attributes: 'data-entity-type' })
    ) {
      writer.setAttribute(
        'dataEntityType',
        viewItem.getAttribute('data-entity-type'),
        image,
      );
      attributesToConsume.push('data-entity-type');
    }

    // Try to place the image in the allowed position.
    if (!safeInsert(image, data.modelCursor)) {
      return;
    }

    // Mark given element as consumed. Now other converters will not process it
    // anymore.
    consumable.consume(viewItem, {
      name: true,
      attributes: attributesToConsume,
    });

    // Make sure `modelRange` and `modelCursor` is up to date after inserting
    // new nodes into the model.
    updateConversionResult(image, data);
  }

  return (dispatcher) => {
    dispatcher.on('element:img', converter, { priority: 'high' });
  };
}

/**
 * Modified alternative implementation of linkimageediting.js' downcastImageLink.
 *
 * @return {function}
 *  Callback that binds an event to its parameter.
 *
 * @private
 */
function downcastBlockImageLink() {
  /**
   * Callback for the attribute:linkHref event.
   *
   * @type {converterHandler}
   */
  function converter(event, data, conversionApi) {
    if (!conversionApi.consumable.consume(data.item, event.name)) {
      return;
    }

    // The image will be already converted - so it will be present in the view.
    const image = conversionApi.mapper.toViewElement(data.item);
    const writer = conversionApi.writer;

    // 1. Create an empty link element.
    const linkElement = writer.createContainerElement('a', {
      href: data.attributeNewValue,
    });
    // 2. Insert link before the associated image.
    writer.insert(writer.createPositionBefore(image), linkElement);
    // 3. Move the image into the link.
    writer.move(
      writer.createRangeOn(image),
      writer.createPositionAt(linkElement, 0),
    );

    // Modified alternative implementation of GHS' addBlockImageLinkAttributeConversion().
    // This is happening here as well to avoid a race condition with the link
    // element not yet existing.
    if (
      conversionApi.consumable.consume(
        data.item,
        'attribute:htmlLinkAttributes:imageBlock',
      )
    ) {
      setViewAttributes(
        conversionApi.writer,
        data.item.getAttribute('htmlLinkAttributes'),
        linkElement,
      );
    }
  }

  return (dispatcher) => {
    dispatcher.on('attribute:linkHref:imageBlock', converter, {
      priority: 'high',
    });
  };
}

/**
 * Add handling of 'dataEntityUuid', 'dataEntityType', 'isDecorative', 'width',
 * 'height' attributes on image elements.
 *
 * @private
 */
class DrupalImageEditing extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return ['ImageUtils'];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageEditing';
  }

  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    const { conversion } = editor;
    const { schema } = editor.model;

    if (schema.isRegistered('imageInline')) {
      schema.extend('imageInline', {
        allowAttributes: [
          'dataEntityUuid',
          'dataEntityType',
          'isDecorative',
          'width',
          'height',
        ],
      });
    }

    if (schema.isRegistered('imageBlock')) {
      schema.extend('imageBlock', {
        allowAttributes: [
          'dataEntityUuid',
          'dataEntityType',
          'isDecorative',
          'width',
          'height',
        ],
      });
    }

    // Conversion.
    conversion
      .for('upcast')
      .add(viewImageToModelImage(editor))
      .attributeToAttribute({
        view: {
          name: 'img',
          key: 'width',
        },
        model: {
          key: 'width',
          value: (viewElement) => {
            if (isNumberString(viewElement.getAttribute('width'))) {
              return `${viewElement.getAttribute('width')}px`;
            }
            return `${viewElement.getAttribute('width')}`;
          },
        },
      })
      .attributeToAttribute({
        view: {
          name: 'img',
          key: 'height',
        },
        model: {
          key: 'height',
          value: (viewElement) => {
            if (isNumberString(viewElement.getAttribute('height'))) {
              return `${viewElement.getAttribute('height')}px`;
            }
            return `${viewElement.getAttribute('height')}`;
          },
        },
      });

    conversion
      .for('downcast')
      .add(modelEntityUuidToDataAttribute())
      .add(modelEntityTypeToDataAttribute());

    conversion
      .for('dataDowncast')
      .add(viewCaptionToCaptionAttribute(editor))
      .elementToElement({
        model: 'imageBlock',
        view: (modelElement, { writer }) =>
          createImageViewElement(writer, 'imageBlock'),
        converterPriority: 'high',
      })
      .elementToElement({
        model: 'imageInline',
        view: (modelElement, { writer }) =>
          createImageViewElement(writer, 'imageInline'),
        converterPriority: 'high',
      })
      .add(modelImageStyleToDataAttribute())
      .add(modelImageWidthToAttribute())
      .add(modelImageHeightToAttribute())
      .add(downcastBlockImageLink());
  }
}

;// CONCATENATED MODULE: ./node_modules/@ckeditor/ckeditor5-image/src/imagetextalternative/imagetextalternativecommand.js
/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module image/imagetextalternative/imagetextalternativecommand
 */



/**
 * The image text alternative command. It is used to change the `alt` attribute of `<imageBlock>` and `<imageInline>` model elements.
 *
 * @extends module:core/command~Command
 */
class ImageTextAlternativeCommand extends delegated_corefrom_dll_reference_CKEditor5.Command {
	/**
	 * The command value: `false` if there is no `alt` attribute, otherwise the value of the `alt` attribute.
	 *
	 * @readonly
	 * @observable
	 * @member {String|Boolean} #value
	 */

	/**
	 * @inheritDoc
	 */
	refresh() {
		const editor = this.editor;
		const imageUtils = editor.plugins.get( 'ImageUtils' );
		const element = imageUtils.getClosestSelectedImageElement( this.editor.model.document.selection );

		this.isEnabled = !!element;

		if ( this.isEnabled && element.hasAttribute( 'alt' ) ) {
			this.value = element.getAttribute( 'alt' );
		} else {
			this.value = false;
		}
	}

	/**
	 * Executes the command.
	 *
	 * @fires execute
	 * @param {Object} options
	 * @param {String} options.newValue The new value of the `alt` attribute to set.
	 */
	execute( options ) {
		const editor = this.editor;
		const imageUtils = editor.plugins.get( 'ImageUtils' );
		const model = editor.model;
		const imageElement = imageUtils.getClosestSelectedImageElement( model.document.selection );

		model.change( writer => {
			writer.setAttribute( 'alt', options.newValue, imageElement );
		} );
	}
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imagealternativetext/drupalimagealternativetextediting.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words imagealternativetext drupalimagealternativetextediting drupalimagetextalternativecommand textalternativemissingview imagetextalternativecommand */

/**
 * @module drupalImage/imagealternativetext/drupalimagealternativetextediting
 */




/**
 * The Drupal image alternative text editing plugin.
 *
 * Registers the `imageTextAlternative` command.
 *
 * @extends module:core/plugin~Plugin
 *
 * @internal
 */
class DrupalImageTextAlternativeEditing extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return ['ImageUtils'];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageAlternativeTextEditing';
  }

  constructor(editor) {
    super(editor);

    /**
     * Keeps references to instances of `TextAlternativeMissingView`.
     *
     * @member {Set<module:drupalImage/imagetextalternative/ui/textalternativemissingview~TextAlternativeMissingView>} #_missingAltTextViewReferences
     * @private
     */
    this._missingAltTextViewReferences = new Set();
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;

    editor.conversion
      .for('editingDowncast')
      .add(this._imageEditingDowncastConverter('attribute:alt', editor))
      // Including changes to src ensures the converter will execute for images
      // that do not yet have alt attributes, as we specifically want to add the
      // missing alt text warning to images without alt attributes.
      .add(this._imageEditingDowncastConverter('attribute:src', editor));

    editor.commands.add(
      'imageTextAlternative',
      new ImageTextAlternativeCommand(this.editor),
    );

    editor.editing.view.on('render', () => {
      // eslint-disable-next-line no-restricted-syntax
      for (const view of this._missingAltTextViewReferences) {
        // Destroy view instances that are not connected to the DOM to ensure
        // there are no memory leaks.
        // https://developer.mozilla.org/en-US/docs/Web/API/Node/isConnected
        if (!view.button.element.isConnected) {
          view.destroy();
          this._missingAltTextViewReferences.delete(view);
        }
      }
    });
  }

  /**
   * Helper that generates model to editing view converters to display missing
   * alt text warning.
   *
   * @param {string} eventName
   *   The name of the event the converter should be attached to.
   *
   * @return {function}
   *   A function that attaches downcast converter to the conversion dispatcher.
   *
   * @private
   */
  _imageEditingDowncastConverter(eventName) {
    const converter = (evt, data, conversionApi) => {
      const editor = this.editor;
      const imageUtils = editor.plugins.get('ImageUtils');
      if (!imageUtils.isImage(data.item)) {
        return;
      }

      const viewElement = conversionApi.mapper.toViewElement(data.item);
      const existingWarning = Array.from(viewElement.getChildren()).find(
        (child) => child.getCustomProperty('drupalImageMissingAltWarning'),
      );
      const hasAlt = data.item.hasAttribute('alt');

      if (hasAlt) {
        // Remove existing warning if alt text is set and there's an existing
        // warning.
        if (existingWarning) {
          conversionApi.writer.remove(existingWarning);
        }
        return;
      }

      // Nothing to do if alt text doesn't exist and there's already an existing
      // warning.
      if (existingWarning) {
        return;
      }

      const view = editor.ui.componentFactory.create(
        'drupalImageAlternativeTextMissing',
      );
      view.listenTo(editor.ui, 'update', () => {
        const selectionRange = editor.model.document.selection.getFirstRange();
        const imageRange = editor.model.createRangeOn(data.item);
        // Set the view `isSelected` property depending on whether the model
        // element associated to the view element is in the selection.
        view.set({
          isSelected:
            selectionRange.containsRange(imageRange) ||
            selectionRange.isIntersecting(imageRange),
        });
      });
      view.render();

      // Add reference to the created view element so that it can be destroyed
      // when the view is no longer connected.
      this._missingAltTextViewReferences.add(view);

      const html = conversionApi.writer.createUIElement(
        'span',
        {
          class: 'image-alternative-text-missing-wrapper',
        },
        function (domDocument) {
          const wrapperDomElement = this.toDomElement(domDocument);
          wrapperDomElement.appendChild(view.element);

          return wrapperDomElement;
        },
      );

      conversionApi.writer.setCustomProperty(
        'drupalImageMissingAltWarning',
        true,
        html,
      );
      conversionApi.writer.insert(
        conversionApi.writer.createPositionAt(viewElement, 'end'),
        html,
      );
    };
    return (dispatcher) => {
      dispatcher.on(eventName, converter, { priority: 'low' });
    };
  }
}

// EXTERNAL MODULE: delegated ./ui.js from dll-reference CKEditor5.dll
var delegated_uifrom_dll_reference_CKEditor5 = __webpack_require__("ckeditor5/src/ui.js");
;// CONCATENATED MODULE: ./node_modules/@ckeditor/ckeditor5-image/src/image/ui/utils.js
/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module image/image/ui/utils
 */



/**
 * A helper utility that positions the
 * {@link module:ui/panel/balloon/contextualballoon~ContextualBalloon contextual balloon} instance
 * with respect to the image in the editor content, if one is selected.
 *
 * @param {module:core/editor/editor~Editor} editor The editor instance.
 */
function repositionContextualBalloon( editor ) {
	const balloon = editor.plugins.get( 'ContextualBalloon' );

	if ( editor.plugins.get( 'ImageUtils' ).getClosestSelectedImageWidget( editor.editing.view.document.selection ) ) {
		const position = getBalloonPositionData( editor );

		balloon.updatePosition( position );
	}
}

/**
 * Returns the positioning options that control the geometry of the
 * {@link module:ui/panel/balloon/contextualballoon~ContextualBalloon contextual balloon} with respect
 * to the selected element in the editor content.
 *
 * @param {module:core/editor/editor~Editor} editor The editor instance.
 * @returns {module:utils/dom/position~Options}
 */
function getBalloonPositionData( editor ) {
	const editingView = editor.editing.view;
	const defaultPositions = delegated_uifrom_dll_reference_CKEditor5.BalloonPanelView.defaultPositions;
	const imageUtils = editor.plugins.get( 'ImageUtils' );

	return {
		target: editingView.domConverter.mapViewToDom( imageUtils.getClosestSelectedImageWidget( editingView.document.selection ) ),
		positions: [
			defaultPositions.northArrowSouth,
			defaultPositions.northArrowSouthWest,
			defaultPositions.northArrowSouthEast,
			defaultPositions.southArrowNorth,
			defaultPositions.southArrowNorthWest,
			defaultPositions.southArrowNorthEast,
			defaultPositions.viewportStickyNorth
		]
	};
}

// EXTERNAL MODULE: delegated ./utils.js from dll-reference CKEditor5.dll
var delegated_utilsfrom_dll_reference_CKEditor5 = __webpack_require__("ckeditor5/src/utils.js");
;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imagealternativetext/ui/imagealternativetextformview.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words focustracker keystrokehandler labeledfield labeledfieldview buttonview viewcollection focusables focuscycler switchbuttonview imagealternativetextformview imagealternativetext */

/**
 * @module drupalImage/imagealternativetext/ui/imagealternativetextformview
 */





/**
 * A class rendering alternative text form view.
 *
 * @extends module:ui/view~View
 *
 * @internal
 */
class ImageAlternativeTextFormView extends delegated_uifrom_dll_reference_CKEditor5.View {
  /**
   * @inheritdoc
   */
  constructor(locale) {
    super(locale);

    /**
     * Tracks information about the DOM focus in the form.
     *
     * @readonly
     * @member {module:utils/focustracker~FocusTracker}
     */
    this.focusTracker = new delegated_utilsfrom_dll_reference_CKEditor5.FocusTracker();

    /**
     * An instance of the {@link module:utils/keystrokehandler~KeystrokeHandler}.
     *
     * @readonly
     * @member {module:utils/keystrokehandler~KeystrokeHandler}
     */
    this.keystrokes = new delegated_utilsfrom_dll_reference_CKEditor5.KeystrokeHandler();

    /**
     * A toggle for marking the image as decorative.
     *
     * @member {module:ui/button/switchbuttonview~SwitchButtonView} #decorativeToggle
     */
    this.decorativeToggle = this._decorativeToggleView();

    /**
     * An input with a label.
     *
     * @member {module:ui/labeledfield/labeledfieldview~LabeledFieldView} #labeledInput
     */
    this.labeledInput = this._createLabeledInputView();

    /**
     * A button used to submit the form.
     *
     * @member {module:ui/button/buttonview~ButtonView} #saveButtonView
     */
    this.saveButtonView = this._createButton(
      Drupal.t('Save'),
      delegated_corefrom_dll_reference_CKEditor5.icons.check,
      'ck-button-save',
    );
    this.saveButtonView.type = 'submit';
    // Save button is disabled when image is not decorative and alt text is
    // empty.
    this.saveButtonView
      .bind('isEnabled')
      .to(
        this.decorativeToggle,
        'isOn',
        this.labeledInput,
        'isEmpty',
        (isDecorativeToggleOn, isLabeledInputEmpty) =>
          isDecorativeToggleOn || !isLabeledInputEmpty,
      );

    /**
     * A button used to cancel the form.
     *
     * @member {module:ui/button/buttonview~ButtonView} #cancelButtonView
     */
    this.cancelButtonView = this._createButton(
      Drupal.t('Cancel'),
      delegated_corefrom_dll_reference_CKEditor5.icons.cancel,
      'ck-button-cancel',
      'cancel',
    );

    /**
     * A collection of views which can be focused in the form.
     *
     * @member {module:ui/viewcollection~ViewCollection}
     *
     * @readonly
     * @protected
     */
    this._focusables = new delegated_uifrom_dll_reference_CKEditor5.ViewCollection();

    /**
     * Helps cycling over focusables in the form.
     *
     * @member {module:ui/focuscycler~FocusCycler}
     *
     * @readonly
     * @protected
     */
    this._focusCycler = new delegated_uifrom_dll_reference_CKEditor5.FocusCycler({
      focusables: this._focusables,
      focusTracker: this.focusTracker,
      keystrokeHandler: this.keystrokes,
      actions: {
        // Navigate form fields backwards using the Shift + Tab keystroke.
        focusPrevious: 'shift + tab',

        // Navigate form fields forwards using the Tab key.
        focusNext: 'tab',
      },
    });

    this.setTemplate({
      tag: 'form',

      attributes: {
        class: [
          'ck',
          'ck-text-alternative-form',
          'ck-text-alternative-form--with-decorative-toggle',
          'ck-responsive-form',
        ],

        // https://github.com/ckeditor/ckeditor5-image/issues/40
        tabindex: '-1',
      },

      children: [
        {
          tag: 'div',
          attributes: {
            class: ['ck', 'ck-text-alternative-form__decorative-toggle'],
          },
          children: [this.decorativeToggle],
        },
        this.labeledInput,
        this.saveButtonView,
        this.cancelButtonView,
      ],
    });

    (0,delegated_uifrom_dll_reference_CKEditor5.injectCssTransitionDisabler)(this);
  }

  /**
   * @inheritdoc
   */
  render() {
    super.render();

    this.keystrokes.listenTo(this.element);

    (0,delegated_uifrom_dll_reference_CKEditor5.submitHandler)({ view: this });

    [
      this.decorativeToggle,
      this.labeledInput,
      this.saveButtonView,
      this.cancelButtonView,
    ].forEach((v) => {
      // Register the view as focusable.
      this._focusables.add(v);

      // Register the view in the focus tracker.
      this.focusTracker.add(v.element);
    });
  }

  /**
   * @inheritdoc
   */
  destroy() {
    super.destroy();

    this.focusTracker.destroy();
    this.keystrokes.destroy();
  }

  /**
   * Creates the button view.
   *
   * @param {String} label
   *   The button label
   * @param {String} icon
   *   The button's icon.
   * @param {String} className
   *   The additional button CSS class name.
   * @param {String} [eventName]
   *   The event name that the ButtonView#execute event will be delegated to.
   * @returns {module:ui/button/buttonview~ButtonView}
   *   The button view instance.
   *
   * @private
   */
  _createButton(label, icon, className, eventName) {
    const button = new delegated_uifrom_dll_reference_CKEditor5.ButtonView(this.locale);

    button.set({
      label,
      icon,
      tooltip: true,
    });

    button.extendTemplate({
      attributes: {
        class: className,
      },
    });

    if (eventName) {
      button.delegate('execute').to(this, eventName);
    }

    return button;
  }

  /**
   * Creates an input with a label.
   *
   * @returns {module:ui/labeledfield/labeledfieldview~LabeledFieldView}
   *   Labeled field view instance.
   *
   * @private
   */
  _createLabeledInputView() {
    const labeledInput = new delegated_uifrom_dll_reference_CKEditor5.LabeledFieldView(
      this.locale,
      delegated_uifrom_dll_reference_CKEditor5.createLabeledInputText,
    );

    labeledInput
      .bind('class')
      .to(this.decorativeToggle, 'isOn', (value) => (value ? 'ck-hidden' : ''));
    labeledInput.label = Drupal.t('Text alternative');

    return labeledInput;
  }

  /**
   * Creates a decorative image toggle view.
   *
   * @return {module:ui/button/switchbuttonview~SwitchButtonView}
   *   Decorative image toggle view instance.
   *
   * @private
   */
  _decorativeToggleView() {
    const decorativeToggle = new delegated_uifrom_dll_reference_CKEditor5.SwitchButtonView(this.locale);
    decorativeToggle.set({
      withText: true,
      label: Drupal.t('Decorative image'),
    });
    decorativeToggle.on('execute', () => {
      decorativeToggle.set('isOn', !decorativeToggle.isOn);
    });

    return decorativeToggle;
  }
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imagealternativetext/ui/missingalternativetextview.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words imagetextalternative missingalternativetextview imagealternativetext */



/**
 * @module drupalImage/imagealternativetext/ui/missingalternativetextview
 */

/**
 * A class rendering missing alt text view.
 *
 * @extends module:ui/view~View
 *
 * @internal
 */
class MissingAlternativeTextView extends delegated_uifrom_dll_reference_CKEditor5.View {
  /**
   * @inheritdoc
   */
  constructor(locale) {
    super(locale);

    const bind = this.bindTemplate;
    this.set('isVisible');
    this.set('isSelected');

    const label = Drupal.t('Add missing alternative text');
    this.button = new delegated_uifrom_dll_reference_CKEditor5.ButtonView(locale);
    this.button.set({
      label,
      tooltip: false,
      withText: true,
    });

    this.setTemplate({
      tag: 'span',
      attributes: {
        class: [
          'image-alternative-text-missing',
          bind.to('isVisible', (value) => (value ? '' : 'ck-hidden')),
        ],
        title: label,
      },
      children: [this.button],
    });
  }
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imagealternativetext/drupalimagealternativetextui.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalimagealternativetextui contextualballoon componentfactory imagealternativetextformview missingalternativetextview imagetextalternativeui imagealternativetext */

/**
 * @module drupalImage/imagealternativetext/drupalimagealternativetextui
 */







/**
 * The Drupal-specific image alternative text UI plugin.
 *
 * This plugin is based on a version of the upstream alternative text UI plugin.
 * This override enhances the UI with a new form element which allows marking
 * images explicitly as decorative. This plugin also provides a UI component
 * that can be displayed on images that are missing alternative text.
 *
 * The logic related to visibility, positioning, and keystrokes are unchanged
 * from the upstream implementation.
 *
 * The plugin uses the contextual balloon.
 *
 * @see module:image/imagetextalternative/imagetextalternativeui~ImageTextAlternativeUI
 * @see module:ui/panel/balloon/contextualballoon~ContextualBalloon
 *
 * @extends module:core/plugin~Plugin
 *
 * @internal
 */
class DrupalImageAlternativeTextUi extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [delegated_uifrom_dll_reference_CKEditor5.ContextualBalloon];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageTextAlternativeUI';
  }

  /**
   * @inheritdoc
   */
  init() {
    this._createButton();
    this._createForm();
    this._createMissingAltTextComponent();

    const showAlternativeTextForm = () => {
      const imageUtils = this.editor.plugins.get('ImageUtils');
      // Show form after upload if there's an image widget in the current
      // selection.
      if (
        imageUtils.getClosestSelectedImageWidget(
          this.editor.editing.view.document.selection,
        )
      ) {
        this._showForm();
      }
    };

    if (this.editor.commands.get('insertImage')) {
      const insertImage = this.editor.commands.get('insertImage');
      insertImage.on('execute', showAlternativeTextForm);
    }
    if (this.editor.plugins.has('ImageUploadEditing')) {
      const imageUploadEditing = this.editor.plugins.get('ImageUploadEditing');
      imageUploadEditing.on('uploadComplete', showAlternativeTextForm);
    }
  }

  /**
   * Creates a missing alt text view which can be displayed within image widgets
   * where the image is missing alt text.
   *
   * The component is registered in the editor component factory.
   *
   * @see module:ui/componentfactory~ComponentFactory
   *
   * @private
   */
  _createMissingAltTextComponent() {
    this.editor.ui.componentFactory.add(
      'drupalImageAlternativeTextMissing',
      (locale) => {
        const view = new MissingAlternativeTextView(locale);
        view.listenTo(view.button, 'execute', () => {
          // If the form is already in the balloon, it needs to be removed to
          // avoid having multiple instances of the form in the balloon. This
          // happens only in the edge case where this event is executed while
          // the form is still in the balloon.
          if (this._isInBalloon) {
            this._balloon.remove(this._form);
          }
          this._showForm();
        });
        view.listenTo(this.editor.ui, 'update', () => {
          view.set({ isVisible: !this._isVisible || !view.isSelected });
        });
        return view;
      },
    );
  }

  /**
   * @inheritdoc
   */
  destroy() {
    super.destroy();

    // Destroy created UI components as they are not automatically destroyed
    // @see https://github.com/ckeditor/ckeditor5/issues/1341
    this._form.destroy();
  }

  /**
   * Creates a button showing the balloon panel for changing the image text
   * alternative and registers it in the editor component factory.
   *
   * @see module:ui/componentfactory~ComponentFactory
   *
   * @private
   */
  _createButton() {
    const editor = this.editor;
    editor.ui.componentFactory.add('drupalImageAlternativeText', (locale) => {
      const command = editor.commands.get('imageTextAlternative');
      const view = new delegated_uifrom_dll_reference_CKEditor5.ButtonView(locale);

      view.set({
        label: Drupal.t('Change image alternative text'),
        icon: delegated_corefrom_dll_reference_CKEditor5.icons.lowVision,
        tooltip: true,
      });

      view.bind('isEnabled').to(command, 'isEnabled');

      this.listenTo(view, 'execute', () => {
        this._showForm();
      });

      return view;
    });
  }

  /**
   * Creates the text alternative form view.
   *
   * @private
   */
  _createForm() {
    const editor = this.editor;
    const view = editor.editing.view;
    const viewDocument = view.document;
    const imageUtils = editor.plugins.get('ImageUtils');

    /**
     * The contextual balloon plugin instance.
     *
     * @private
     * @member {module:ui/panel/balloon/contextualballoon~ContextualBalloon}
     */
    this._balloon = this.editor.plugins.get('ContextualBalloon');

    /**
     * A form used for changing the `alt` text value.
     *
     * @member {module:drupalImage/imagetextalternative/ui/imagealternativetextformview~ImageAlternativeTextFormView}
     */
    this._form = new ImageAlternativeTextFormView(editor.locale);

    // Render the form so its #element is available for clickOutsideHandler.
    this._form.render();

    this.listenTo(this._form, 'submit', () => {
      editor.execute('imageTextAlternative', {
        newValue: this._form.decorativeToggle.isOn
          ? ''
          : this._form.labeledInput.fieldView.element.value,
      });

      this._hideForm(true);
    });

    this.listenTo(this._form, 'cancel', () => {
      this._hideForm(true);
    });

    // Reposition the toolbar when the decorative toggle is executed because
    // it has an impact on the form size.
    this.listenTo(this._form.decorativeToggle, 'execute', () => {
      repositionContextualBalloon(editor);
    });

    // Close the form on Esc key press.
    this._form.keystrokes.set('Esc', (data, cancel) => {
      this._hideForm(true);
      cancel();
    });

    // Reposition the balloon or hide the form if an image widget is no longer
    // selected.
    this.listenTo(editor.ui, 'update', () => {
      if (!imageUtils.getClosestSelectedImageWidget(viewDocument.selection)) {
        this._hideForm(true);
      } else if (this._isVisible) {
        repositionContextualBalloon(editor);
      }
    });

    // Close on click outside of balloon panel element.
    (0,delegated_uifrom_dll_reference_CKEditor5.clickOutsideHandler)({
      emitter: this._form,
      activator: () => this._isVisible,
      contextElements: [this._balloon.view.element],
      callback: () => this._hideForm(),
    });
  }

  /**
   * Shows the form in the balloon.
   *
   * @private
   */
  _showForm() {
    if (this._isVisible) {
      return;
    }

    const editor = this.editor;
    const command = editor.commands.get('imageTextAlternative');
    const decorativeToggle = this._form.decorativeToggle;
    const labeledInput = this._form.labeledInput;

    this._form.disableCssTransitions();

    if (!this._isInBalloon) {
      this._balloon.add({
        view: this._form,
        position: getBalloonPositionData(editor),
      });
    }

    decorativeToggle.isOn = command.value === '';

    // Make sure that each time the panel shows up, the field remains in sync
    // with the value of the command. If the user typed in the input, then
    // canceled the balloon (`labeledInput#value` stays unaltered) and re-opened
    // it without changing the value of the command, they would see the old
    // value instead of the actual value of the command.
    // https://github.com/ckeditor/ckeditor5-image/issues/114
    labeledInput.fieldView.element.value = command.value || '';
    labeledInput.fieldView.value = labeledInput.fieldView.element.value;

    if (!decorativeToggle.isOn) {
      labeledInput.fieldView.select();
    } else {
      decorativeToggle.focus();
    }

    this._form.enableCssTransitions();
  }

  /**
   * Removes the form from the balloon.
   *
   * @param {Boolean} [focusEditable=false]
   *   Controls whether the editing view is focused afterwards.
   *
   * @private
   */
  _hideForm(focusEditable) {
    if (!this._isInBalloon) {
      return;
    }

    // Blur the input element before removing it from DOM to prevent issues in
    // some browsers.
    // See https://github.com/ckeditor/ckeditor5/issues/1501.
    if (this._form.focusTracker.isFocused) {
      this._form.saveButtonView.focus();
    }

    this._balloon.remove(this._form);

    if (focusEditable) {
      this.editor.editing.view.focus();
    }
  }

  /**
   * Returns `true` when the form is the visible view in the balloon.
   *
   * @type {Boolean}
   *
   * @private
   */
  get _isVisible() {
    return this._balloon.visibleView === this._form;
  }

  /**
   * Returns `true` when the form is in the balloon.
   *
   * @type {Boolean}
   *
   * @private
   */
  get _isInBalloon() {
    return this._balloon.hasView(this._form);
  }
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/drupalimagealternativetext.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words imagealternativetext imagetextalternativeediting drupalimagealternativetextediting drupalimagealternativetextui */

/**
 * @module drupalImage/imagealternativetext
 */





/**
 * The Drupal-specific image text alternative plugin.
 *
 * This has been implemented based on the CKEditor 5 built in image alternative
 * text plugin. This plugin enhances the original upstream form with a toggle
 * button that allows users to explicitly mark images as decorative, which is
 * downcast to an empty `alt` attribute. This plugin also provides a warning for
 * images that are missing the `alt` attribute, to ensure content authors don't
 * leave the alternative text blank by accident.
 *
 * @see module:image/imagetextalternative~ImageTextAlternative
 *
 * @extends module:core/plugin~Plugin
 */
class DrupalImageAlternativeText extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalImageTextAlternativeEditing, DrupalImageAlternativeTextUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageAlternativeText';
  }
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/drupalimage.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalimageediting drupalimagealternativetext */





/**
 * @private
 */
class DrupalImage extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalImageEditing, DrupalImageAlternativeText];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImage';
  }
}

/* harmony default export */ const drupalimage = (DrupalImage);

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imageupload/drupalimageuploadediting.js
/* eslint-disable import/no-extraneous-dependencies */


/**
 * Adds Drupal-specific attributes to the CKEditor 5 image element.
 *
 * @private
 */
class DrupalImageUploadEditing extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    const imageUploadEditing = editor.plugins.get('ImageUploadEditing');
    imageUploadEditing.on('uploadComplete', (evt, { data, imageElement }) => {
      editor.model.change((writer) => {
        writer.setAttribute('dataEntityUuid', data.response.uuid, imageElement);
        writer.setAttribute(
          'dataEntityType',
          data.response.entity_type,
          imageElement,
        );
      });
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageUploadEditing';
  }
}

// EXTERNAL MODULE: delegated ./upload.js from dll-reference CKEditor5.dll
var delegated_uploadfrom_dll_reference_CKEditor5 = __webpack_require__("ckeditor5/src/upload.js");
;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imageupload/drupalimageuploadadapter.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words simpleuploadadapter filerepository */

/**
 * Upload adapter.
 *
 * Copied from @ckeditor5/ckeditor5-upload/src/adapters/simpleuploadadapter
 *
 * @private
 * @implements module:upload/filerepository~UploadAdapter
 */
class DrupalImageUploadAdapter {
  /**
   * Creates a new adapter instance.
   *
   * @param {module:upload/filerepository~FileLoader} loader
   *   The file loader.
   * @param {module:upload/adapters/simpleuploadadapter~SimpleUploadConfig} options
   *   The upload options.
   */
  constructor(loader, options) {
    /**
     * FileLoader instance to use during the upload.
     *
     * @member {module:upload/filerepository~FileLoader} #loader
     */
    this.loader = loader;

    /**
     * The configuration of the adapter.
     *
     * @member {module:upload/adapters/simpleuploadadapter~SimpleUploadConfig} #options
     */
    this.options = options;
  }

  /**
   * Starts the upload process.
   *
   * @see module:upload/filerepository~UploadAdapter#upload
   * @return {Promise}
   *   Promise that the upload will be processed.
   */
  upload() {
    return this.loader.file.then(
      (file) =>
        new Promise((resolve, reject) => {
          this._initRequest();
          this._initListeners(resolve, reject, file);
          this._sendRequest(file);
        }),
    );
  }

  /**
   * Aborts the upload process.
   *
   * @see module:upload/filerepository~UploadAdapter#abort
   */
  abort() {
    if (this.xhr) {
      this.xhr.abort();
    }
  }

  /**
   * Initializes the `XMLHttpRequest` object using the URL specified as
   *
   * {@link module:upload/adapters/simpleuploadadapter~SimpleUploadConfig#uploadUrl `simpleUpload.uploadUrl`} in the editor's
   * configuration.
   */
  _initRequest() {
    this.xhr = new XMLHttpRequest();

    this.xhr.open('POST', this.options.uploadUrl, true);
    this.xhr.responseType = 'json';
  }

  /**
   * Initializes XMLHttpRequest listeners
   *
   * @private
   *
   * @param {Function} resolve
   *  Callback function to be called when the request is successful.
   * @param {Function} reject
   *  Callback function to be called when the request cannot be completed.
   * @param {File} file
   *  Native File object.
   */
  _initListeners(resolve, reject, file) {
    const xhr = this.xhr;
    const loader = this.loader;
    const genericErrorText = `Couldn't upload file: ${file.name}.`;

    xhr.addEventListener('error', () => reject(genericErrorText));
    xhr.addEventListener('abort', () => reject());
    xhr.addEventListener('load', () => {
      const response = xhr.response;

      if (!response || response.error) {
        return reject(
          response && response.error && response.error.message
            ? response.error.message
            : genericErrorText,
        );
      }
      // Resolve with the `urls` property and pass the response
      // to allow customizing the behavior of features relying on the upload adapters.
      resolve({
        response,
        urls: { default: response.url },
      });
    });

    // Upload progress when it is supported.
    if (xhr.upload) {
      xhr.upload.addEventListener('progress', (evt) => {
        if (evt.lengthComputable) {
          loader.uploadTotal = evt.total;
          loader.uploaded = evt.loaded;
        }
      });
    }
  }

  /**
   * Prepares the data and sends the request.
   *
   * @param {File} file
   *   File instance to be uploaded.
   */
  _sendRequest(file) {
    // Set headers if specified.
    const headers = this.options.headers || {};

    // Use the withCredentials flag if specified.
    const withCredentials = this.options.withCredentials || false;

    Object.keys(headers).forEach((headerName) => {
      this.xhr.setRequestHeader(headerName, headers[headerName]);
    });

    this.xhr.withCredentials = withCredentials;

    // Prepare the form data.
    const data = new FormData();

    data.append('upload', file);

    // Send the request.
    this.xhr.send(data);
  }
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imageupload/drupalfilerepository.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words uploadurl drupalimageuploadadapter  */






/**
 * Provides a Drupal upload adapter.
 *
 * @private
 */
class DrupalFileRepository extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [delegated_uploadfrom_dll_reference_CKEditor5.FileRepository];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalFileRepository';
  }

  /**
   * @inheritdoc
   */
  init() {
    const options = this.editor.config.get('drupalImageUpload');

    if (!options) {
      return;
    }

    if (!options.uploadUrl) {
      (0,delegated_utilsfrom_dll_reference_CKEditor5.logWarning)('simple-upload-adapter-missing-uploadurl');

      return;
    }

    this.editor.plugins.get(delegated_uploadfrom_dll_reference_CKEditor5.FileRepository).createUploadAdapter = (loader) => {
      return new DrupalImageUploadAdapter(loader, options);
    };
  }
}

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/imageupload/drupalimageupload.js
/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupalimageuploadediting drupalfilerepository */





/**
 * Integrates the CKEditor image upload with Drupal.
 *
 * @private
 */
class DrupalImageUpload extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalFileRepository, DrupalImageUploadEditing];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalImageUpload';
  }
}

/* harmony default export */ const drupalimageupload = (DrupalImageUpload);

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/insertimage/drupalinsertimage.js
/* eslint-disable import/no-extraneous-dependencies */


/**
 * Provides a toolbar item for inserting images.
 *
 * @private
 */
class DrupalInsertImage extends delegated_corefrom_dll_reference_CKEditor5.Plugin {
  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    editor.ui.componentFactory.add('drupalInsertImage', () => {
      // Use upstream insertImage component when ImageInsertUI is enabled. The
      // upstream insertImage button supports inserting of external images
      // and uploading images. Out-of-the-box Drupal only uses the insertImage
      // button for inserting external images.
      if (editor.plugins.has('ImageInsertUI')) {
        return editor.ui.componentFactory.create('insertImage');
      }
      // If ImageInsertUI plugin is not enabled, fallback to using uploadImage
      // upstream button.
      if (editor.plugins.has('ImageUpload')) {
        return editor.ui.componentFactory.create('uploadImage');
      }

      throw new Error(
        'drupalInsertImage requires either ImageUpload or ImageInsertUI plugin to be enabled.',
      );
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalInsertImage';
  }
}

/* harmony default export */ const drupalinsertimage = (DrupalInsertImage);

;// CONCATENATED MODULE: ./ckeditor5_plugins/drupalImage/src/index.js
// cspell:ignore imageupload imageresize insertimage drupalimage drupalimageupload drupalimageresize drupalinsertimage





/**
 * @private
 */
/* harmony default export */ const src = ({
  DrupalImage: drupalimage,
  DrupalImageUpload: drupalimageupload,
  DrupalInsertImage: drupalinsertimage,
});

})();

__webpack_exports__ = __webpack_exports__["default"];
/******/ 	return __webpack_exports__;
/******/ })()
;
});