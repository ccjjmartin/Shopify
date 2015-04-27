(function ($) {

  Drupal.shopify = {
    ctx: {},
    settings: {}
  };

  /**
   * Display an "Added to cart" message by sending a POST request to the backend.
   */
  Drupal.shopify.display_add_to_cart_message = function (el) {
    var $el = $(el);
    $.post('/shopify/added-to-cart', {
      product_id: $el.data('product-id'),
      variant_id: $el.data('variant-id')
    });
  };

  Drupal.behaviors.shopify = {
    attach: function (context, settings) {
      Drupal.shopify.ctx = context;
      Drupal.shopify.settings = settings;
    }
  }

}(jQuery));