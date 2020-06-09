/**
 * @file
 * Figshare embed.
 */

CKEDITOR.dialog.add('figshare', function (editor) {
  'use strict';

  const regExp = /^(((https?:)?\/\/|www\.)(widgets\.)?figshare\.com\/articles(\/[^\/]+)?\/([0-9]+))/i;

  return {
    title: 'Insert Figshare',
    minWidth: 350,
    minHeight: 80,
    contents: [
      {
        id: 'info',
        elements: [
          {
            type: 'text',
            id: 'url',
            label: editor.lang.common.url,
            required: true,
            validate: function () {
              if (!regExp.test(this.getValue())) {
                return 'The specified URL is not supported. You must use a link from figshare.com or widgets.figshare.com.';
              }
              return true;
            },
            setup: function (widget) {
              this.setValue(widget.data.src);
            },
            commit: function (widget) {
              const match = regExp.exec(this.getValue());
              const src = 'https://widgets.figshare.com/articles/' + match[6] + '/embed';
              widget.setData('src', src);
            }
          },
          {
            type: 'text',
            id: 'width',
            label: 'Width (px)',
            setup: function (widget) {
              this.setValue(widget.data.width);
            },
            commit: function (widget) {
              widget.setData('width', this.getValue());
            }
          },
          {
            type: 'text',
            id: 'height',
            label: 'Height (px)',
            setup: function (widget) {
              this.setValue(widget.data.height);
            },
            commit: function (widget) {
              widget.setData('height', this.getValue());
            }
          }
        ]
      }
    ]
  };
});
