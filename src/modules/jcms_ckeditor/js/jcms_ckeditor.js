/**
 * CKEditor Inline
 */
(function ($) {
  'use strict';

  Drupal.behaviors.inlineEditor = {
    attach: function(context, settings) {

      if ($('.node__content .field--name-field-impact-statement', context).length > 0) {
        $('.node__content .field--name-field-impact-statement', context).appendTo($('.block-page-title-block .content', context));
      }

      // Make sure we have the content field to process
      if ($('.node__content .field--name-field-content-html-preview', context).length > 0) {
        var autosaveTimer;
        
        // Takes in two CKEditor Node Lists containing images
        // and finds any uuid of images that are no longer
        // in the updated list (i.e. deleted)
        var diff = function (original, updated) {
          var deletedIds = [], ids = [], id, i;
          // Get array of ids in the updated list
          if (updated.count() > 0) {
            for (i = 0; i < updated.count(); i++) {
              id = updated.getItem(i).data('uuid');
              if (id) {
                ids.push(id);
              }
            }
          }
          // Find any ids in the original list not in the updated list
          if (original.count() > 0) {
            for (i = 0; i < original.count(); i++) {
              id = original.getItem(i).data('uuid');
              if (id && ids.indexOf(id) < 0) {
                deletedIds.push(id);
              }
            }
          }
          return deletedIds;
        };
        
        // Contrib plugins
        CKEDITOR.plugins.addExternal('embedbase', settings.pluginPathContrib + 'embedbase/');
        CKEDITOR.plugins.addExternal('embedvideo', settings.pluginPathContrib + 'embedvideo/');
        CKEDITOR.plugins.addExternal('balloonpanel', settings.pluginPathContrib + 'balloonpanel/');
        CKEDITOR.plugins.addExternal('balloontoolbar', settings.pluginPathContrib + 'balloontoolbar/');
        CKEDITOR.plugins.addExternal('autoembed', settings.pluginPathContrib + 'autoembed/');
        CKEDITOR.plugins.addExternal('filetools', settings.pluginPathContrib + 'filetools/');
        CKEDITOR.plugins.addExternal('notificationaggregator', settings.pluginPathContrib + 'notificationaggregator/');
        CKEDITOR.plugins.addExternal('uploadwidget', settings.pluginPathContrib + 'uploadwidget/');
        CKEDITOR.plugins.addExternal('uploadimage', settings.pluginPathContrib + 'uploadimage/');
        CKEDITOR.plugins.addExternal('autolink', settings.pluginPathContrib + 'autolink/');
        CKEDITOR.plugins.addExternal('undo', settings.pluginPathContrib + 'undo/');
        CKEDITOR.plugins.addExternal('sharedspace', settings.pluginPathContrib + 'sharedspace/');
        CKEDITOR.plugins.addExternal('fakeobjects', settings.pluginPathContrib + 'fakeobjects/');
        CKEDITOR.plugins.addExternal('link', settings.pluginPathContrib + 'link/');
        CKEDITOR.plugins.addExternal('codesnippet', settings.pluginPathContrib + 'codesnippet/');
        
        // Custom plugins
        CKEDITOR.plugins.addExternal('imagealign', settings.pluginPathCustom + 'imagealign/');
        CKEDITOR.plugins.addExternal('elifebutton', settings.pluginPathCustom + 'elifebutton/');
        CKEDITOR.plugins.addExternal('captionedvideo', settings.pluginPathCustom + 'captionedvideo/');
        
        var $content = $(' .node__content .field--name-field-content-html-preview');
        
        $content.attr('contenteditable', true);
        
        var uuid = false, url, data, options, node_type;

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
        
        var bodyEditorOptions = {
          extraPlugins: 'image2,uploadimage,balloontoolbar,balloonpanel,imagealign,elifebutton,captionedvideo,autoembed,pastefromword,undo,sharedspace,link,codesnippet',
          toolbarGroups: [
            {"name":"basicstyles","groups":["basicstyles"]},
            {"name":"links","groups":["links"]},
            {"name":"paragraph","groups":["list","blocks"]},
            //{"name":"document","groups":["mode"]},
            {"name":"insert","groups":["insert"]},
            {"name":"undo","groups":["undo"]},
            {"name":"codesnippet","groups":["codesnippet"]},
            {"name": "styles"}
          ],
          imageUploadUrl: '/jsonapi/file/image',
          removeButtons: 'Underline,Strike,Anchor,SpecialChar,HorizontalRule,ImageAlignLeft,ImageAlignRight,ImageFullWidth,Styles',
          image2_alignClasses: ['align-left', 'align-center', 'profile-left'],
          image2_disableResizer: true,
          extraAllowedContent: 'elifebutton[data-href](elife-button--default,elife-button--outline);oembed[data-videocaption](align-left,align-right,align-center);figure;figcaption;iframe[!src,width,height];img[data-fid,data-uuid];placeholder;a',
          format_tags: 'p;h1;h2',
          embed_provider: '//ckeditor.iframe.ly/api/oembed?url={url}&callback={callback}',
          autoEmbed_widget: 'embedVideo',
          customConfig: '',
          stylesSet: false,
          linkShowAdvancedTab: false,
          linkShowTargetTab: false,
          sharedSpaces: {top: 'cke-floating-toolbar'}
        };

        // Get the session token for crsf
        $.ajax({
          method: 'GET',
          url: '/rest/session/token',
          success: function(response){
            ajaxOptions.headers['X-CSRF-Token'] = response;
          }
        });
        
        if (uuid) {
          // Disable autoinline as we are going to create a shared space toolbar
          CKEDITOR.disableAutoInline = true;
          
          // Remove link type option and unwanted protocols
          // from link dialog window
          CKEDITOR.on('dialogDefinition', function (event) {
            var dialogName = event.data.name;
            var dialogDefinition = event.data.definition;

            if (dialogName == 'link') {
              var infoTab = dialogDefinition.getContents('info');
              infoTab.get('linkType').hidden = true;
              infoTab.get('protocol')['items'].splice(2, 3);
            }
            
          });
          
          var bodyEditor = $content.ckeditor(bodyEditorOptions).editor;
          
          bodyEditor.on( 'instanceReady', function(ck) {
            var editable = bodyEditor.editable(), images = editable.find('img');
            var notification = new CKEDITOR.plugins.notification(bodyEditor, {message: 'Test'});
            
            // Remove items from context menus
            //bodyEditor.removeMenuItem('paste');
            bodyEditor.removeMenuItem('cut');
            //bodyEditor.removeMenuItem('copy');
            bodyEditor.removeMenuItem('image');
            
            // Insert a figure widget when image is uploaded with fid and uuid
            bodyEditor.widgets.registered.uploadimage.onUploaded = function(upload) {
              this.replaceWith( '<figure class="image"><img src="' + upload.url + '" ' +
                'width="' + upload.responseData.width + '" ' +
                'height="' + upload.responseData.height + '" ' +
                'data-fid="' + upload.responseData.fid + '" ' +
                'data-uuid="' + upload.responseData.uuid + '">' +
                '<figcaption>Caption</figcaption></figure>');
              // force images list to be rebuilt
              images = editable.find('img');
            };
            
            // Balloon toolbar for figure/image alignment
            bodyEditor.balloonToolbars.create ({
              buttons: 'ImageAlignLeft,ImageFullWidth,ImageAlignRight',
              widgets: 'image'
            });

            // Any change in the editor contents
            bodyEditor.on('change', function() {
              // Hide any previous autosave notification and
              // autosave content 5 seconds after last change
              notification.hide();
              clearTimeout(autosaveTimer);
              autosaveTimer = setTimeout(saveBodyEditor, 5000);
              
              // Look for any deleted images and if so remove
              // from backend
              var deletedIds = diff(images, editable.find('img'));
              if (deletedIds.length > 0) {
                for (var i=0; i<deletedIds.length; i++) {
                  $.ajax({
                    method: 'DELETE',
                    dataType: 'json',
                    contentType: 'application/vnd.api+json',
                    url: '/jsonapi/file/image/' + deletedIds[i]
                  });
                }
              }
              images = editable.find('img');
            });

            // Callback if save is successful
            var saveSuccess = function(response, status) {
              var msg = Drupal.t('Save successful');
              notification.update({message: msg, duration: 3000, type: 'info'});
              notification.show();
            };

            // Callback if autosave is successful
            var saveAutoSuccess = function(response, status) {
              var msg = Drupal.t('Auto save successful');
              notification.update({message: msg, duration: 3000, type: 'info'});
              notification.show();
            };
            
            // Callback if autosave fails
            var saveError = function(xhr, status, error) {
              var msg = Drupal.t('Auto save failed');
              if (xhr && xhr.responseJSON && xhr.responseJSON.errors[0]) {
                var err = xhr.responseJSON.errors[0];
                msg += '<br>' + err.title + ': ' + err.detail;
              }
              notification.update({message: msg, duration: 0, type: 'warning'});
              notification.show();
            };
            
            // Save the main field content
            var saveBodyEditor = function(showSaveNotification){
              // Remove any hidden placeholder text
              $(bodyEditor.editable().$).find('placeholder').remove();
              var content = bodyEditor.getData();
              if ($.trim(content).length === 0) {
                // if we are left with an empty string 
                // reinstace placeholder
                bodyEditor.setData('<p><placeholder>' + settings.placeholder + '</placeholder></p>');
              }
              images = editable.find('img');
              var fids = [], extraOptions;
              for (var i = 0; i < images.count(); i++) {
                var fid = images.getItem(i).data('fid');
                if (fid) fids.push({target_id: fid});
              }
              data = {
                data: {
                  type: "node--" + node_type,
                  id: uuid,
                  attributes: {
                    field_content_html_preview: {
                      value: bodyEditor.getData(),
                      format: 'ckeditor_html'
                    },
                    field_content_images_preview: fids
                  }
                }            
              };
              extraOptions = {
                data: JSON.stringify(data),  
                error: saveError
              };
              if (typeof showSaveNotification === 'undefined' || (typeof showSaveNotification === 'boolean' && showSaveNotification === true)) {
                extraOptions.success = saveAutoSuccess;
              } else if (typeof showSaveNotification === 'function') {
                extraOptions.success = showSaveNotification;
              }
              options = $.extend({}, ajaxOptions, extraOptions);
              $.ajax(options);
              clearTimeout(autosaveTimer);
            };
            
            // Save any changes when editor looses focus
            bodyEditor.on('blur' , function(e) {
              saveBodyEditor();
            });
            
            // Save image in backend when receive upload request
            bodyEditor.on('fileUploadRequest', function(e) {
              var image = e.data.fileLoader.data.split(',');
              if (image[0] === 'data:image/jpeg;base64' ||
                  image[0] === 'data:image/png;base64') {
                data = {
                  data: {
                    type: "file--image",
                    attributes: {
                      data: image[1],
                      uri: 'public://' + settings.imageFileDirectory + '/' + e.data.fileLoader.fileName
                    }
                  }            
                };
                var xhr = e.data.fileLoader.xhr;

                xhr.setRequestHeader('Content-Type', 'application/vnd.api+json');
                xhr.setRequestHeader('Accept', 'application/vnd.api+json');
                xhr.setRequestHeader('X-CSRF-Token', ajaxOptions.headers['X-CSRF-Token']);
                xhr.send(JSON.stringify(data));

                // Prevent the default behavior.
                e.stop();
              } else {
                // Image format not recognised
                e.cancel();
              }
            });

            // Handle response from file save
            bodyEditor.on('fileUploadResponse', function(e) {
              // Prevent the default response handler.
              e.stop();

              // Get XHR and response.
              var data = e.data, xhr = data.fileLoader.xhr;

              if (xhr.status == 201) { 
                // New file created so set attributes so they are
                // available to the editor
                var response = JSON.parse(xhr.responseText);
                var attr = response.data.attributes;
                data.url = attr.url;
                data.fid = attr.fid;
                data.uuid = attr.uuid;
                data.width = attr.field_image_width;
                data.height = attr.field_image_height;
              } else {
                // File upload error
                e.cancel();
              }
            });
            
            // Save when editor gains focus to help initial paste work
            bodyEditor.on('focus', function(e) {
              saveBodyEditor(false);
            });
            
            $('.save-button').once('save').each(function(){
              $(this).click(function(event){
                event.preventDefault();
                saveBodyEditor(saveSuccess);
              });
            });

            $('.discard-button').once('discard').each(function(){
              $(this).click(function(event){
                if (!confirm('You are about to discard your changes?')) {
                  event.preventDefault();
                }
              });
            });
            
          });
          
        }
      }
      
      // Position the toolbar div as the window scrolls
      var toolbarScroll = function() {
        var $window = $(window);
        var $toolbar = $('#cke-floating-toolbar');
        var $container = $toolbar.parent();
        var defaultTop = $('article.node').offset().top;
        var containerLeft = $container.offset().left;
        var containerWidth = $container.width();
        var toolbarHeight = 0;
        if ($('.toolbar-bar').length > 0) {
          toolbarHeight += $('.toolbar-bar').height();
        }
        if ($('.toolbar-tray-horizontal.is-active').length > 0) {
          toolbarHeight += $('.toolbar-tray-horizontal.is-active').height();
        }
        
        if ($window.scrollTop() > defaultTop - toolbarHeight) {
          $toolbar.addClass('cke_toolbar_fixed');
          $toolbar.css('top', toolbarHeight).css('left', containerLeft).css('width', containerWidth);
        }
        else {
          $toolbar.removeClass('cke_toolbar_fixed');
          $toolbar.css('top', 'auto').css('left', 'auto').css('width', 'auto');
        }
      };
      
      if ($('#cke-floating-toolbar').length > 0) {
        $('#cke-floating-toolbar').once('toolbar').each( function(){
          $(window).scroll(function(){
            toolbarScroll();
          });
        });
      }
      
    }
    
  };
  
})(jQuery);
