/**
 * @file
 * Javascript to integrate the clipboard.js library with Drupal.
 */

(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.clipboardSimpleJS = {
        attach: function (context, settings) {
            var $clipboard = $('.clipboard-simple input');
            $clipboard.once('simplified').each(function () {
                var text = $(this).val(), buttonText = $(this).data('buttonText');

                $('<button class="button clipboard-simple-btn" data-clipboard-text="' + text + '">' + buttonText + '</button>').insertAfter(this);
            });

            var clipboardSimples = new ClipboardJS('.clipboard-simple-btn');
            $clipboard.hide();

            clipboardSimples.on('success', function (e) {
                var $button = $(e.trigger);
                $button.prop('title', 'Copied!');
                $button.tooltip({
                   position: { my: "center", at: "right+30" }
                }).mouseover();

                setTimeout(function () {
                    $button.tooltip('destroy');
                    $button.prop('title', '');
                }, 1500);
            });

            clipboardSimples.on('error', function (e) {
                var $button = $(e.trigger);
                $button.prev('input').show().focus().select();
                $button.remove();
            });
        }
    };
})(jQuery, Drupal, drupalSettings);
