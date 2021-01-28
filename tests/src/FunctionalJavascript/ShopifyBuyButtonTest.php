<?php

namespace Drupal\Tests\shopify\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Shopify buy button integration test.
 *
 * @group shopify
 */
class ShopifyBuyButtonTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['shopify', 'shopify_test'];

  /**
   * Dummy product that refers to a real product id.
   *
   * @var \Drupal\shopify\Entity\ShopifyProduct
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    user_role_grant_permissions('anonymous', ['view shopify product entities']);
    $variant = \Drupal::entityTypeManager()->getStorage('shopify_product_variant')->create([
      'title' => 'Test product variant',
      'inventory_quantity' => 1,
      // This is a real product variant for the product below on
      // dropsify.myshopify.com.
      'variant_id' => '38010633683121',
    ]);
    $variant->save();
    $product = \Drupal::entityTypeManager()->getStorage('shopify_product')->create([
      'title' => 'Test product',
      // This is a real product on dropsify.myshopify.com.
      'product_id' => '6226485674161',

    ]);
    $product->set('variants', [$variant]);
    $product->save();
    $this->product = $product;
  }

  /**
   * Tests the buy button loads with expected options.
   */
  public function testBuyButtonLoads() {
    $this->drupalGet($this->product->toUrl()->toString());
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $button = $assert_session->waitForElementVisible('css', '.shopify-buy__btn');
    $this->assertEquals($button->getText(), 'Add to cart');
    $assert_session->elementExists('css', 'option[value="9"]');
    $assert_session->elementExists('css', 'option[value="8"]');
    $assert_session->elementExists('css', 'option[value="7"]');
  }

  /**
   * Tests that a product can be added to the cart.
   */
  public function testAddProductToCart() {
    $this->drupalGet($this->product->toUrl()->toString());
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $button = $assert_session->waitForElementVisible('css', '.shopify-buy__btn');
    $button->click();
    $cart = $assert_session->waitForElementVisible('css', '.shopify-buy__cart');
    $assert_session->waitForElementRemoved('css', '.shopify-buy__cart-empty-text');
    $items = $cart->findAll('css', '.shopify-buy__cart-item');
    $this->assertEquals(1, count($items));
    $first_item = $items[0];
    $this->assertEquals('Drupal', $first_item->find('css', '.shopify-buy__cart-item__title')->getText());
    $this->assertEquals('9', $first_item->find('css', '.shopify-buy__cart-item__variant-title')->getText());
  }

}
