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
      const buttonLayout = settings.config.button.layout;
      const cartInterface = settings.config.cart.interface;
      const { templates } = settings;

      const client = ShopifyBuy.buildClient({
        domain: settings.config.api.domain,
        storefrontAccessToken: settings.config.api.storefront_access_token
      });
      ShopifyBuy.UI.onReady(client).then((ui) => {
        const options = {
          product: {
            iframe: false,
            templates: templates.product,
            contents: {
              img: false,
              title: false,
              price: false
            },
            text: {
              button: buttonLayout.button_text
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
              button: cartInterface.checkout_button_label,
              noteDescription: cartInterface.order_note_label
            },
            contents: {
              note: true
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

        const config = {
          options
        };

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
