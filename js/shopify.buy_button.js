/**
 * @file
 * Defines Javascript behaviors for the Shopify buy button.
 */

((Drupal, drupalSettings, ShopifyBuy) => {
  Drupal.behaviors.shopify = {
    attach() {
      const settings = drupalSettings.shopify.buyButton;
      const buttonStyle = settings.config.button.styles;
      const buttonLayout = settings.config.button.layout;
      const cartInterface = settings.config.cart.interface;
      const cartStyle = settings.config.cart.styles;

      const client = ShopifyBuy.buildClient({
        domain: settings.config.api.domain,
        storefrontAccessToken: settings.config.api.storefront_access_token
      });
      ShopifyBuy.UI.onReady(client).then((ui) => {
        const options = {
          product: {
            styles: {
              button: {
                'font-size': `${buttonStyle.font_size}px`,
                'padding-top': `${buttonStyle.font_size}px`,
                'padding-bottom': `${buttonStyle.font_size}px`,
                color: buttonStyle.text_color,
                ':hover': {
                  color: buttonStyle.text_color,
                  'background-color': buttonStyle.background_color
                },
                'background-color': buttonStyle.background_color,
                ':focus': {
                  'background-color': buttonStyle.background_color
                },
                'border-radius': `${buttonStyle.corner_radius}px`,
                'padding-left': `${buttonStyle.width}px`,
                'padding-right': `${buttonStyle.width}px`
              },
              quantityInput: {
                'font-size': `${buttonStyle.font_size}px`,
                'padding-top': `${buttonStyle.font_size}px`,
                'padding-bottom': `${buttonStyle.font_size}px`
              },
              buttonWrapper: {
                'text-align': buttonLayout.alignment
              }
            },
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
            styles: {
              button: {
                'font-size': `${buttonStyle.font_size}px`,
                'padding-top': `${buttonStyle.font_size}px`,
                'padding-bottom': `${buttonStyle.font_size}px`,
                color: buttonStyle.text_color,
                ':hover': {
                  color: buttonStyle.text_color,
                  'background-color': buttonStyle.background_color
                },
                'background-color': buttonStyle.background_color,
                ':focus': {
                  'background-color': buttonStyle.background_color
                },
                'border-radius': `${buttonStyle.corner_radius}px`
              },
              title: {
                color: cartStyle.text_color
              },
              header: {
                color: cartStyle.text_color
              },
              lineItems: {
                color: cartStyle.text_color
              },
              subtotalText: {
                color: cartStyle.text_color
              },
              subtotal: {
                color: cartStyle.text_color
              },
              notice: {
                color: cartStyle.text_color
              },
              currency: {
                color: cartStyle.text_color
              },
              close: {
                color: cartStyle.text_color,
                ':hover': {
                  color: cartStyle.text_color
                }
              },
              empty: {
                color: cartStyle.text_color
              },
              noteDescription: {
                color: cartStyle.text_color
              },
              discountText: {
                color: cartStyle.text_color
              },
              discountIcon: {
                fill: cartStyle.text_color
              },
              discountAmount: {
                color: cartStyle.text_color
              },
              cart: {
                'background-color': cartStyle.background_color
              },
              footer: {
                'background-color': cartStyle.background_color
              }
            },
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
            styles: {
              toggle: {
                'background-color': buttonStyle.background_color,
                ':hover': {
                  'background-color': buttonStyle.background_color
                },
                ':focus': {
                  'background-color': buttonStyle.background_color
                }
              },
              count: {
                'font-size': `${buttonStyle.font_size}px`,
                color: buttonStyle.text_color,
                ':hover': {
                  color: buttonStyle.text_color
                }
              },
              iconPath: {
                fill: buttonStyle.text_color
              }
            }
          },
          lineItem: {
            styles: {
              variantTitle: {
                color: cartStyle.text_color
              },
              title: {
                color: cartStyle.text_color
              },
              price: {
                color: cartStyle.text_color
              },
              fullPrice: {
                color: cartStyle.text_color
              },
              discount: {
                color: cartStyle.text_color
              },
              discountIcon: {
                fill: cartStyle.text_color
              },
              quantity: {
                color: cartStyle.text_color
              },
              quantityIncrement: {
                color: cartStyle.text_color,
                'border-color': cartStyle.text_color
              },
              quantityDecrement: {
                color: cartStyle.text_color,
                'border-color': cartStyle.text_color
              },
              quantityInput: {
                color: cartStyle.text_color,
                'border-color': cartStyle.text_color
              }
            }
          }
        };

        const config = {
          moneyFormat: '%24%7B%7Bamount%7D%7D',
          options
        };

        if (settings.product) {
          config.id = settings.product.id;
          config.node = document.getElementById(settings.product.html_id);
        }

        ui.createComponent(settings.product ? 'product' : 'cart', config);
      });
    }
  };
})(Drupal, drupalSettings, ShopifyBuy);
