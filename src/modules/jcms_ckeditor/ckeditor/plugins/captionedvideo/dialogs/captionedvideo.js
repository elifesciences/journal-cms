/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/* global alert */

CKEDITOR.dialog.add( 'captionedVideo', function( editor ) {
	'use strict';

	return {
		title: 'Insert Video',
		minWidth: 350,
		minHeight: 50,

		onLoad: function() {
			var that = this,
				loadContentRequest = null;

			this.on( 'ok', function( evt ) {
				// We're going to hide it manually, after remote response is fetched.
				evt.data.hide = false;

				// We don't want the widget system to finalize widget insertion (it happens with priority 20).
				evt.stop();

				// Indicate visually that waiting for the response (https://dev.ckeditor.com/ticket/13213).
				that.setState( CKEDITOR.DIALOG_STATE_BUSY );

				var url = that.getValueOf( 'info', 'url' );
                  for (var i = 0; i < this.widget.element.$.childNodes.length; i++) {
                    if (that.widget.element.$.childNodes[i].localName == 'figcaption') {
                      if (that.getValueOf( 'info', 'captioned' )) {
                        that.widget.element.addClass('with-caption');
                        that.widget.element.removeClass('no-caption');
                      } else {
                        // We could clear the caption here if we are happy that
                        // removing caption also deletes it.
                        that.widget.element.addClass('no-caption');
                        that.widget.element.removeClass('with-caption');
                      } 
                    }
                  }
				loadContentRequest = that.widget.loadContent( url, {
					noNotifications: true,

					callback: function() {
						if ( !that.widget.isReady() ) {
							editor.widgets.finalizeCreation( that.widget.wrapper.getParent( true ) );
						}

						editor.fire( 'saveSnapshot' );

						that.hide();
						unlock();
					},

					errorCallback: function( messageTypeOrMessage ) {
						that.getContentElement( 'info', 'url' ).select();

						alert( that.widget.getErrorMessage( messageTypeOrMessage, url, 'Given' ) );

						unlock();
					}
				} );
			}, null, null, 15 );

			this.on( 'cancel', function( evt ) {
				if ( evt.data.hide && loadContentRequest ) {
					loadContentRequest.cancel();
					unlock();
				}
			} );

			function unlock() {
				// Visual waiting indicator is no longer needed (https://dev.ckeditor.com/ticket/13213).
				that.setState( CKEDITOR.DIALOG_STATE_IDLE );
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

						setup: function( widget ) {
							this.setValue( widget.data.url );
						},

						validate: function() {
							if ( !this.getDialog().widget.isUrlValid( this.getValue() ) ) {
								return 'The specified URL is not supported.';
							}

							return true;
						}
					},
                      {
                          type: 'checkbox',
                          id: 'captioned',
                          label: 'Captioned video',
                            
                          setup: function( widget ) {
							this.setValue( widget.element.hasClass( 'with-caption' ) );
						},  
                      }
				]
			}
		]
	};
} );
