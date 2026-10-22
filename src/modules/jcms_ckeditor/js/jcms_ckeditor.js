
/**
 * CKEditor Inline
 */
(function ($) {
  'use strict';

  const VALIDATE = 1;
  const PUBLISH = 2;
  const VALIDATE_AND_PUBLISH = 3;

  Drupal.CKEditor5Instances = new Map();

  Drupal.behaviors.inlineEditor = {
    attach: function (context, settings) {

      toastr.options.closeButton = true;
      toastr.options.positionClass = "toast-top-center";
      toastr.options.showDuration = 100;
      toastr.options.hideDuration = 30;

      if ($('.node__content .field--name-field-impact-statement', context).length > 0) {
        $('.node__content .field--name-field-impact-statement', context).appendTo($('.block-page-title-block .content', context));
      }

      // Make sure we have the content field to process
      if ($('.node__content .field--name-field-content-html-preview', context).length > 0) {
        var autosaveTimer, isDirty = false;
        var uuid = false, url, data, option, node_type, currentImages;

        const getCkImages = function(editor) {
          const range = editor.model.createRangeIn(editor.model.document.getRoot());
          const images = new Set();
          for (const value of range.getWalker()) {
            if (value.type === 'elementStart' &&
              (value.item.name === 'imageInline' || value.item.name === 'imageBlock') &&
              value.item.hasAttribute('dataEntityUuid')) {
              images.add(value.item.getAttribute('dataEntityUuid'));
            }
          }
          return images;
        }

        const difference = function(setA, setB) {
          const _difference = new Set(setA);
          for (const elem of setB) {
            _difference.delete(elem);
          }
          return _difference;
        }

        // Get UUID and node type from body tag
        if ($('body').data('uuid') && $('body').data('node-type')) {
          node_type = $('body').data('node-type');
          uuid = $('body').data('uuid');
          url = '/jsonapi/node/' + node_type + '/' + uuid;
        }

        var ajaxOptions = {
          method: 'PATCH',
          dataType: 'json',
          accepts: {json: 'application/vnd.api+json'},
          contentType: 'application/vnd.api+json',
          url: url,
          processData: false,
          headers: []
        };

        // Get the session token for crsf
        $.ajax({
          method: 'GET',
          url: '/session/token',
          success: function (response) {
            ajaxOptions.headers['X-CSRF-Token'] = response;
          }
        });

        var setElementId = function setElementId(element) {
          var id = Math.random().toString().slice(2, 9);
          element.setAttribute('data-ckeditor5-id', id);
          return id;
        };

        var getElementId = function getElementId(element) {
          return element.getAttribute('data-ckeditor5-id');
        };

        $(once('ckeditor5-load', 'body', context)).each(function () {
          var editorDecoupled = CKEditor5.editorDecoupled;
          var DecoupledEditor = editorDecoupled.DecoupledEditor;

          var editorSetting = settings.editor.formats.ckeditor_html.editorSettings,
            toolbar = editorSetting.toolbar,
            plugins = editorSetting.plugins,
            pluginConfig = editorSetting.config,
            language = editorSetting.language;

          var config = _objectSpread({
            extraPlugins: selectPlugins(plugins),
            toolbar: toolbar,
            language: language
          }, processConfig(pluginConfig));

          var element = document.querySelector('.node__content');
          var id = setElementId(element);

          DecoupledEditor.create(element, config).then(function (editor) {
            Drupal.CKEditor5Instances.set(id, editor);
            if (typeof CKEditorInspector !== 'undefined') {
              CKEditorInspector.attach(editor);
            }
            var toolbar = document.getElementById('cke5-floating-toolbar');
            toolbar.appendChild(editor.ui.view.toolbar.element);
            const editorModel = editor.model;
            const editorDocument = editorModel.document;

            // Save any changes when editor looses focus.
            // Hide placeholder when editor gets focus.
            editor.ui.focusTracker.on('change:isFocused' , function (e) {
              if (isDirty || $.trim(editor.getData()).length === 0) {
                saveEditor(!e.source.isFocused);
              }
              if (e.source.isFocused) {
                let root = editorDocument.getRoot();
                let children = root.getChildren();
                for (let child of children) {
                  if (child.is('element', 'placeholder')) {
                    editorModel.change(writer => {
                      writer.remove(child);
                    });
                  }
                }
              }
            });

            editorDocument.on('change:data', function () {
              // Hide any previous autosave notification and
              // autosave content 5 seconds after last change
              toastr.clear();
              clearTimeout(autosaveTimer);
              isDirty = true;
              autosaveTimer = setTimeout(saveEditor, 5000);

              // Look for any deleted images and if so remove from backend
              if (currentImages && currentImages.size > 0) {
                const deletedImages = difference(currentImages, getCkImages(editor));
                if (deletedImages.size > 0) {
                  deletedImages.forEach((uuid) => {
                    $.ajax({
                      method: 'DELETE',
                      dataType: 'json',
                      contentType: 'application/vnd.api+json',
                      headers: {'X-CSRF-Token': ajaxOptions.headers['X-CSRF-Token']},
                      url: '/jsonapi/file/image/' + uuid
                    });
                  });
                }
              }
              currentImages = getCkImages(editor);
            });
          }).catch(function (error) {
            console.error(error);
          });
        });

        const toastStyle = $('<style>').text('#toast-container{top:0px}').appendTo(document.head);

        // Position the toolbar div as the window scrolls
        var toolbarScroll = function () {
          const $window = $(window);
          const $toolbar = $('#cke5-floating-toolbar');
          const $container = $toolbar.parent();
          const $toast = $('#toast-container');
          const defaultTop = $('article.node').offset().top;
          const containerLeft = $container.offset().left;
          const containerWidth = $container.width();
          var toolbarHeight = 0;
          if ($('.toolbar-bar').length > 0) {
            toolbarHeight += $('.toolbar-bar').height();
          }
          if ($('.toolbar-tray-horizontal.is-active').length > 0) {
            toolbarHeight += $('.toolbar-tray-horizontal.is-active').height();
          }

          var scrollTop = (defaultTop - toolbarHeight) - $window.scrollTop();
          var toastTop = 0;
          if (scrollTop < 0) {
            $toolbar.addClass('cke_toolbar_fixed');
            $toolbar.css('top', toolbarHeight).css('left', containerLeft).css('width', containerWidth);
            toastTop = toolbarHeight + 50;
          } else {
            $toolbar.removeClass('cke_toolbar_fixed');
            $toolbar.css('top', 'auto').css('left', 'auto').css('width', 'auto');
            toastTop = toolbarHeight + 50 + scrollTop;
          }
          toastStyle.text('#toast-container{top:' + toastTop + 'px}');
        };

        if ($('#cke5-floating-toolbar').length > 0) {
          toolbarScroll();
          $(once('toolbar', '#cke5-floating-toolbar', context)).each(function () {
            $(window).scroll(function () {
              toolbarScroll();
            });
          });
        }

        // Save the main field content
        var saveEditor = function (showSaveNotification) {
          const element = document.querySelector('.node__content');
          const id = getElementId(element);
          const editor = Drupal.CKEditor5Instances.get(id);
          if (!editor) {
            return;
          }
          const content = editor.getData();

          const image_fields = [];

          if ($.trim(content).length === 0 && !editor.ui.focusTracker.isFocused) {
            // If we have lost focus and are left with an empty
            // string then reinstate the placeholder.
            editor.setData('<placeholder>' + settings.placeholder + '</placeholder>');
          }

          currentImages = getCkImages(editor);
          currentImages.forEach((uuid) => {
            const image_field = {
              id: uuid,
              type: 'file--image',
            }
            image_fields.push(image_field);
          });
          data = {
            data: {
              type: "node--" + node_type,
              id: uuid,
              attributes: {
                field_content_html_preview: {
                  value: content,
                  format: 'ckeditor_html'
                }
              },
              relationships: {
                field_content_images_preview: {data: image_fields}
              }
            }
          };
          var extraOptions = {
            data: JSON.stringify(data),
            error: saveError
          };
          if (typeof showSaveNotification === 'undefined' || (typeof showSaveNotification === 'boolean' && showSaveNotification === true)) {
            extraOptions.success = saveAutoSuccess;
          } else if (typeof showSaveNotification === 'function') {
            extraOptions.success = showSaveNotification;
          }
          const options = $.extend({}, ajaxOptions, extraOptions);
          $.ajax(options);
          clearTimeout(autosaveTimer);
        };

        // Callback if autosave is successful
        var saveAutoSuccess = function (response, status) {
          var msg = Drupal.t('Auto save successful');
          toastr.success(msg);
          isDirty = false;
        };

        // Callback if save and redirect to edit (close)
        var saveCloseSuccess = function (response, status) {
          var msg = Drupal.t('Save successful');
          toastr.success(msg);
          isDirty = false;
          var href = window.location.href;
          if (href.match(/node\/[0-9]+/i)) {
            // Redirect after 1 sec to avoid race condition with node just being saved
            setTimeout(function () {window.location.href = href + "/edit";}, 1000);
          }
        };

        // Callback if publish/save is successful
        var saveSuccess = function (response, status) {
          // Delay validation by 1s because of recent save
          setTimeout(function () {
            $.ajax({
              method: 'GET',
              url: '/validate-publish/' + VALIDATE_AND_PUBLISH + '/' + uuid.substring(uuid.length - 8),
              success: function (response) {
                if (response.validated && response.published) {
                  var msg = Drupal.t('Content has been published.');
                  toastr.success(msg);
                  setTimeout(function () {window.location.reload();}, 1000);
                } else {
                  var msg = Drupal.t('Content validation failed. Content will NOT be published.');
                  toastr.warning(msg);
                }
              },
              error: function (xhr, status, error) {
                var msg = Drupal.t('Content publication failed');
                if (xhr && xhr.responseJSON && xhr.responseJSON.errors[0]) {
                  var err = xhr.responseJSON.errors[0];
                  msg += '<br>' + err.title + ': ' + err.detail;
                }
                else if (xhr && xhr.responseText) {
                  msg += '<br>' + xhr.responseText;
                }
                toastr.warning(msg);
              }
            });
          }, 1000);
        };

        // Callback if autosave fails
        var saveError = function (xhr, status, error) {
          var msg = Drupal.t('Auto save failed');
          if (xhr && xhr.responseJSON && xhr.responseJSON.errors[0]) {
            var err = xhr.responseJSON.errors[0];
            msg += '<br>' + err.title + ': ' + err.detail;
          } else if (xhr && xhr.responseText) {
            msg += '<br>' + xhr.responseText;
          }
          toastr.warning(msg);
        };

        // Save and close button just saves the current text
        $(once('save', '.save-button', context)).each(function () {
          $(this).click(function (event) {
            event.preventDefault();
            saveEditor(saveCloseSuccess);
          });
        });

        // Publish button just saves the current text
        $(once('publish', '.publish-button', context)).each(function () {
          $(this).click(function (event) {
            event.preventDefault();
            saveEditor(saveSuccess);
          });
        });

        // Discard button just saves the current text
        $(once('discard', '.discard-button', context)).each(function () {
          $(this).click(function (event) {
            if (!confirm('You are about to discard your changes?')) {
              event.preventDefault();
            }
          });
        });

        function selectPlugins(plugins) {
          return plugins.map(function (pluginDefinition) {
            var _pluginDefinition$spl = pluginDefinition.split('.'),
              _pluginDefinition$spl2 = _slicedToArray(_pluginDefinition$spl, 2),
              build = _pluginDefinition$spl2[0],
              name = _pluginDefinition$spl2[1];
            if (CKEditor5[build] && CKEditor5[build][name]) {
              return CKEditor5[build][name];
            }
            console.warn("Failed to load ".concat(build, " - ").concat(name));
            return null;
          });
        }

        function buildRegexp(config) {
          var pattern = config.regexp.pattern;
          var main = pattern.match(/\/(.+)\/.*/)[1];
          var options = pattern.match(/\/.+\/(.*)/)[1];
          return new RegExp(main, options);
        }

        function processConfig(config) {
          function processArray(config) {
            return config.map(function (item) {
              if (_typeof(item) === 'object') {
                return processConfig(item);
              }
              return item;
            });
          }

          return Object.entries(config).reduce(function (processed, _ref) {
            var _ref2 = _slicedToArray(_ref, 2),
              key = _ref2[0],
              value = _ref2[1];
            if (_typeof(value) === 'object') {
              if (!value) {
                return processed;
              }
              if (value.hasOwnProperty('func')) {
                processed[key] = buildFunc(value);
              } else if (value.hasOwnProperty('regexp')) {
                processed[key] = buildRegexp(value);
              } else if (Array.isArray(value)) {
                processed[key] = processArray(value);
              } else {
                processed[key] = processConfig(value);
              }
            } else {
              processed[key] = value;
            }
            return processed;
          }, {});
        }
      }
    }

  };

})(jQuery);
