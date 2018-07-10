/**
 * CKEditor plugin to insert an eLife button
 */
CKEDITOR.plugins.add('elifebutton', {
  init: function(editor) {
    editor.addCommand('elifeButton', new CKEDITOR.dialogCommand('elifeButtonDialog'));
    editor.addCommand('elifeButtonRemove', {
      exec: function(editor) {
        var selection = editor.getSelection();
        var element = selection.getStartElement();
        selection.selectElement(element);
        var range = selection.getRanges()[0];
        range.deleteContents();
        range.select();
      }
    });
    editor.ui.addButton('ElifeButton', {
      label: 'Insert eLife Button',
      command: 'elifeButton',
      toolbar: 'insert,130',
      icon: this.path + 'icons/button.png'
    });
    CKEDITOR.dialog.add('elifeButtonDialog', this.path + 'dialogs/dialog.js' );
    editor.on('doubleclick', function(evt) {
      var element = evt.data.element.getAscendant('elifebutton', true);
      if (element && !element.isReadOnly()) {
        if (element.is('elifebutton')) {
          evt.data.dialog = 'elifeButtonDialog';
        }
      }
    }, null, null, 0 );
    if (editor.contextMenu) {
      editor.addMenuGroup('buttonGroup');
      editor.addMenuItem('buttonItem', {
        label: 'Edit Button',
        icon: this.path + 'icons/button.png',
        command: 'elifeButton',
        group: 'buttonGroup'
      });
      editor.addMenuItem('buttonRemove', {
        label: 'Remove Button',
        icon: this.path + 'icons/button.png',
        command: 'elifeButtonRemove',
        group: 'buttonGroup'
      });      
      editor.contextMenu.addListener(function(element) {
        if (element.getAscendant('elifebutton', true) ) {
          return {buttonItem: CKEDITOR.TRISTATE_OFF, buttonRemove: CKEDITOR.TRISTATE_OFF};
        }
      });
    }
  }
});
