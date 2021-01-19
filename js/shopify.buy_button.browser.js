/**
* DO NOT EDIT THIS FILE.
* Edit the corresponding file that does not have the `.browser.js` extension.
**/
"use strict";

/**
 * @file
 * Defines Javascript behaviors for the Shopify buy button.
 */
(function (Drupal, drupalSettings, ShopifyBuy) {
  Drupal.behaviors.shopify = {
    attach: function attach(context) {
      // Do nothing if this is not the initial document load.
      if (context !== document) {
        return;
      }

      var settings = drupalSettings.shopify.buyButton;
      var buttonInterface = settings.config.button.interface;
      var cartInterface = settings.config.cart.interface;
      var templates = settings.templates;
      var client = ShopifyBuy.buildClient({
        domain: settings.config.api.domain,
        storefrontAccessToken: settings.config.api.storefront_access_token
      });
      ShopifyBuy.UI.onReady(client).then(function (ui) {
        var options = {
          product: {
            iframe: false,
            templates: templates.product,
            contents: {
              img: buttonInterface.show_image,
              title: buttonInterface.show_title,
              price: buttonInterface.show_price
            },
            text: {
              button: buttonInterface.button_text
            }
          },
          cart: {
            iframe: false,
            templates: templates.cart,
            text: {
              title: cartInterface.heading_label,
              total: cartInterface.subtotal_label,
              empty: cartInterface.empty_message,
              notice: cartInterface.additional_info_text,
              button: cartInterface.checkout_button_label
            },
            contents: {
              // Overridden below, if configured.
              note: false
            }
          },
          toggle: {
            iframe: false,
            templates: templates.toggle
          },
          lineItem: {
            iframe: false,
            templates: templates.lineItem
          },
          option: {
            iframe: false,
            templates: templates.option
          },
          // Additional unsupported options.
          modal: {},
          productSet: {},
          modalProduct: {}
        };
        var config = {
          options: options
        };

        if (cartInterface.show_order_note) {
          config.options.cart.contents.note = true;
          config.options.cart.text.noteDescription = cartInterface.order_note_label;
        }

        if (templates.money.format) {
          config.moneyFormat = templates.money.format;
        }

        if (settings.product) {
          config.id = settings.product.id;
          config.node = document.getElementById(settings.product.html_id);
        }

        ui.createComponent(settings.product ? 'product' : 'cart', config);
      });
    }
  };
})(Drupal, drupalSettings, ShopifyBuy);