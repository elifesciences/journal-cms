/**
 * @file
 * Figshare embed plugin.
 */

(function () {
  'use strict';

  CKEDITOR.plugins.add('figshareembed', {
    icons: 'figshareembed',
    hidpi: true,
    requires: 'dialog,widget',

    onLoad: function () {
      CKEDITOR.addCss('.no-events{pointer-events: none;}');
    },

    init: function (editor) {
      editor.widgets.add('figshareembed', {
        init: function () {
          this.setData('src', this.element.findOne('iframe').getAttribute('src'));
          this.setData('width', this.element.getAttribute('data-width'));
          this.setData('height', this.element.getAttribute('data-height'));
        },
        data: function () {
          this.element.findOne('iframe').setAttribute('src', this.data.src);
          this.element.setAttribute('data-width', this.data.width);
          this.element.setAttribute('data-height', this.data.height);
        },
        button: 'Insert Figshare',
        dialog: 'figshare',
        template: '<figure class="figshare" data-width="{width}" data-height="{height}"><iframe class="no-events" src="{src}" width="100%" height="400px"></iframe></figure>',
        allowedContent: 'figure(figshare),iframe[!src,width,height](no-events)',
        requiredContent: 'figure',
        upcast: function (element) {
          return element.name === 'figure' && element.hasClass('figshare');
        },
        defaults: {
          width: '600',
          height: '400',
          src: ''
        },
      });

      CKEDITOR.dialog.add('figshare', this.path + 'dialogs/figshareembed.js');
    }

  });

})();
