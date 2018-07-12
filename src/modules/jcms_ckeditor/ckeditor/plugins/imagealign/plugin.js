/**
 * CKEditor plugin to change image alignment
 */
CKEDITOR.plugins.add('imagealign', {
  init: function(editor) {
    var plugin_path = this.path;
    function setImageAlignment(editor, align) {
      var selection = editor.getSelection();
      var element = selection.getStartElement();
      if (CKEDITOR.plugins.widget.isDomWidget(element)) {
        var widget = editor.widgets.getByElement(element);
        if (widget.name == 'image') {
          widget.setData('align', align);
        }
        /*else if (widget.name == 'embedVideo') {
          widget.removeClass('align-left');
          widget.removeClass('align-right');
          widget.removeClass('align-center');
          widget.addClass('align-' + align);
        }*/
      }
    }
    editor.addCommand('imageAlignLeft', {
      exec: function(editor) { setImageAlignment(editor, 'left'); }
    });
    editor.addCommand('imageAlignRight', {
      exec: function(editor) { setImageAlignment(editor, 'right'); }
    });
    editor.addCommand('imageFullWidth', {
      exec: function(editor) { setImageAlignment(editor, 'center'); }
    });
    editor.ui.addButton('ImageAlignLeft', {
      label: 'Align Image Left',
      command: 'imageAlignLeft',
      toolbar: 'insert,100',
      icon: plugin_path + 'icons/alignleft.png'
    });
    editor.ui.addButton('ImageAlignRight', {
      label: 'Align Image Right',
      command: 'imageAlignRight',
      toolbar: 'insert,110',
      icon: plugin_path + 'icons/alignright.png'
    });
    editor.ui.addButton('ImageFullWidth', {
      label: 'Image Full Width',
      command: 'imageFullWidth',
      toolbar: 'insert,120',
      icon: plugin_path + 'icons/fullwidth.png'
    });
  }
});
