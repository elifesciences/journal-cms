/**
 * @module elife-button/elifebutton
 */

/* eslint-disable import/no-extraneous-dependencies */

import { Plugin } from 'ckeditor5/src/core';
import ElifeButtonEditing from './elifebuttonediting';
import ElifeButtonUI from './elifebuttonui';

/**
 * @private
 */
export default class ElifeButton extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [ElifeButtonEditing, ElifeButtonUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'ElifeButton';
  }
}
