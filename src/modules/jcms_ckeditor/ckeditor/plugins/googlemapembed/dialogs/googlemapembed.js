/**
 * @file
 * Google map embed.
 */

CKEDITOR.dialog.add('googlemapembed', function (editor) {
  'use strict';

  return {
      title: 'Insert Google Map',

      onLoad: function () {
        let that = this,
          loadContentRequest = null;

        this.on('ok', function (evt) {
          // We're going to hide it manually, after remote response is fetched.
          evt.data.hide = false;

          // We don't want the widget system to finalize widget insertion (it happens with priority 20).
          evt.stop();

          // Indicate visually that waiting for the response (https://dev.ckeditor.com/ticket/13213).
          that.setState(CKEDITOR.DIALOG_STATE_BUSY);

          const url = that.getValueOf('info', 'url');
          loadContentRequest = that.widget.loadContent(url, {
            noNotifications: true,

            callback: function () {
              if (!that.widget.isReady()) {
                editor.widgets.finalizeCreation(that.widget.wrapper.getParent(true));
              }

              editor.fire('saveSnapshot');

              that.hide();
              unlock();
            },

            errorCallback: function (messageTypeOrMessage) {
              that.getContentElement('info', 'url').select();

              alert(that.widget.getErrorMessage(messageTypeOrMessage, url, 'Given'));

              unlock();
            }
          });
        }, null, null, 15);

        this.on('cancel', function (evt) {
          if (evt.data.hide && loadContentRequest) {
            loadContentRequest.cancel();
            unlock();
          }
        });

        function unlock() {
          // Visual waiting indicator is no longer needed (https://dev.ckeditor.com/ticket/13213).
          that.setState(CKEDITOR.DIALOG_STATE_IDLE);
          loadContentRequest = null;
        }
      },

      contents: [
        {
          id: 'info',

          elements: [
            {
              type: 'text',
              id: 'url',
              label: editor.lang.common.url,
              required: true,

              setup: function (widget) {
                this.setValue(widget.data.url);
              },

              validate: function () {
                if (!this.getDialog().widget.isUrlValid(this.getValue())) {
                  return 'The specified URL is not supported.';
                }
                return true;
              }
            }
          ]
        }
      ]
  };
});
