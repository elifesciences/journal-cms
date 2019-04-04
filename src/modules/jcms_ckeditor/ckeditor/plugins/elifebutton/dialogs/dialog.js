CKEDITOR.dialog.add('elifeButtonDialog', function(editor) {
  return {
    title: 'Button Properties',
    minWidth: 400,
    minHeight: 200,

    contents: [
      {
        id: 'options',
        label: 'Basic Settings',
        elements: [
          {
            type: 'text',
            id: 'buttontext',
            label: 'Button text',
            validate: CKEDITOR.dialog.validate.notEmpty("The text to appear in the button."),
            setup: function(element) {
              this.setValue(element.getText());
            },
            commit: function(element) {
              element.setText(this.getValue());
            }
          },
          {
            type: 'text',
            id: 'buttonlink',
            label: 'Button link',
            validate: CKEDITOR.dialog.validate.notEmpty("The link for the button."),
            setup: function(element) {
              this.setValue(element.getAttribute("data-href"));
            }
          }
        ]
      }
    ],
    
    onShow: function() {
      var selection = editor.getSelection();
      var element = selection.getStartElement();

      if (element) element = element.getAscendant('elifebutton', true);

      if (!element || element.getName() != 'elifebutton') {
        element = editor.document.createElement('elifebutton');        
        this.insertMode = true;
      }
      else {
        this.insertMode = false;
      }
        
      this.element = element;
      if (!this.insertMode) this.setupContent(this.element);
    },
    
    onOk: function() {
      var dialog = this;
      var button = this.element;
      this.commitContent(button);

      button.setAttribute('data-href', dialog.getValueOf('options', 'buttonlink'));
      button.addClass('elife-button--default');
      
      if (this.insertMode) {
        editor.insertElement(button);
      }
      
    }
  };
});
