/**
 * @file
 * Defines Javascript behaviors for the Shopify buy button.
 */

((Drupal, drupalSettings, ShopifyBuy) => {
  Drupal.behaviors.shopify = {
    attach: (context) => {
      // Do nothing if this is not the initial document load.
      if (context !== document) {
        return;
      }

      const settings = drupalSettings.shopify.buyButton;
      const buttonInterface = settings.config.button.interface;
      const cartInterface = settings.config.cart.interface;
      const cartBehaviors = settings.config.cart.behavior;
      const { templates } = settings;

      const client = ShopifyBuy.buildClient({
        domain: settings.config.api.domain,
        storefrontAccessToken: settings.config.api.storefront_access_token
      });
      const ui = ShopifyBuy.UI.init(client);
      const options = {
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
          },
          popup: cartBehaviors.checkout === 'popup'
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

      const config = {
        options
      };

      if (cartInterface.show_order_note) {
        config.options.cart.contents.note = true;
        config.options.cart.text.noteDescription =
          cartInterface.order_note_label;
      }

      if (templates.money.format) {
        config.moneyFormat = templates.money.format;
      }

      if (settings.product) {
        config.id = settings.product.id;
        config.node = document.getElementById(settings.product.html_id);
      }

      ui.createComponent(settings.product ? 'product' : 'cart', config);
    }
  };
})(Drupal, drupalSettings, ShopifyBuy);
