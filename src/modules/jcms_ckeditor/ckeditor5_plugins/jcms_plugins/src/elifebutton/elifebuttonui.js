/**
 * @module elife-button/elifebuttondui
 */

/* eslint-disable import/no-extraneous-dependencies */

import { Plugin, icons } from 'ckeditor5/src/core';
import { createDropdown} from 'ckeditor5/src/ui';
import ElifeButtonFormView from "./ui/elifebuttonformview";
import ElifeButtonEditing from "./elifebuttonediting";
import elifeIcon from '../../../../icons/elifebutton.svg';

/**
 * The elife button UI plugin.
 *
 * @private
 */
export default class ElifeButtonUI extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [ ElifeButtonEditing ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'ElifeButtonUI';
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const t = editor.t;
    const command = editor.commands.get( 'insertElifeButton' );

    editor.ui.componentFactory.add( 'elifeButton', locale => {
      const dropdown = createDropdown( locale );
      const elifeForm = new ElifeButtonFormView( getFormValidators( editor.t ), editor.locale );

      this._setUpDropdown( dropdown, elifeForm, command, elifeIcon, 'Insert Elife Button');
      this._setUpForm( dropdown, elifeForm, command );

      return dropdown;
    } );

    editor.ui.componentFactory.add( 'elifeButtonEdit', locale => {
      const dropdown = createDropdown( locale );
      const elifeForm = new ElifeButtonFormView( getFormValidators( editor.t ), editor.locale );

      this._setUpDropdown( dropdown, elifeForm, command, icons.pencil, 'Edit Elife Button');
      this._setUpForm( dropdown, elifeForm, command );

      return dropdown;
    } );

  }

  /**
   * @private
   * @param {module:ui/dropdown/dropdownview~DropdownView} dropdown
   * @param {module:ui/view~View} form
   * @param {module:elifebutton/elifebuttoncommand~ElifeButtonCommand} command
   * @param {String} type The type of media
   * @param {Object} icon The toolbar icon
   * @param {String} buttonLabel The tooltip to display on the toolbar button
   */
  _setUpDropdown( dropdown, form, command, icon, buttonLabel ) {
    const editor = this.editor;
    const t = editor.t;
    const button = dropdown.buttonView;

    dropdown.bind( 'isEnabled' ).to( command );
    dropdown.panelView.children.add( form );
    dropdown.panelPosition = 'smw';

    button.set( {
      label: t( buttonLabel ),
      icon: icon,
      tooltip: true
    } );

    // Note: Use the low priority to make sure the following listener starts working after the
    // default action of the drop-down is executed (i.e. the panel showed up). Otherwise, the
    // invisible form/input cannot be focused/selected.
    button.on( 'open', () => {
      form.disableCssTransitions();

      // Make sure that each time the panel shows up, the URL field remains in sync with the value of
      // the command. If the user typed in the input, then canceled (`urlInputView#fieldView#value` stays
      // unaltered) and re-opened it without changing the value (e.g. because they
      // didn't change the selection), they would see the old value instead of the actual value of the
      // command.
      form.url = (command.value) ? command.value.url : '';
      form.text = (command.value) ? command.value.text : '';
      form.textInputView.fieldView.select();
      form.enableCssTransitions();
    }, { priority: 'low' } );

    dropdown.on( 'submit', () => {
      if ( form.isValid() ) {
        command.execute( form.url, form.text );
        editor.editing.view.focus();
      }
    } );

    dropdown.on( 'change:isOpen', () => form.resetFormStatus() );
    dropdown.on( 'cancel', () => {
      editor.editing.view.focus();
    } );
  }

  /**
   * @private
   * @param {module:ui/dropdown/dropdownview~DropdownView} dropdown
   * @param {module:ui/view~View} form
   * @param {module:elifebutton/elifebuttoncommand~ElifeButtonCommand} command
   */
  _setUpForm( dropdown, form, command ) {
    form.delegate( 'submit', 'cancel' ).to( dropdown );
    form.urlInputView.bind( 'value' ).to( command, 'value.url' );
    form.textInputView.bind( 'value' ).to( command, 'value.text' );

    // Form elements should be read-only when corresponding commands are disabled.
    form.urlInputView.bind( 'isReadOnly' ).to( command, 'isEnabled', value => !value );
    form.textInputView.bind( 'isReadOnly' ).to( command, 'isEnabled', value => !value );
  }
}

export function getFormValidators( t ) {
  return [
    form => {
      if ( !form.url.length ) {
        return t( 'The URL must not be empty.' );
      }
    },
    form => {
      if ( !form.text.length ) {
        return t( 'The text must not be empty.' );
      }
    }
  ];
}
