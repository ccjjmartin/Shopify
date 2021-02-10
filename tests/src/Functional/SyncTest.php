<?php

namespace Drupal\Tests\shopify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests synchronization behaviors.
 *
 * @group shopify_api
 */
class SyncTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['shopify', 'shopify_test'];

  /**
   * Tests product synchronization.
   */
  public function testProductSync() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));

    $this->drupalGet('/admin/config/system/shopify/sync');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [
      'num_per_batch' => 10,
      'delete_products_first' => FALSE,
      'force_update' => TRUE,
    ], 'Start Products Sync');

    $this->assertText('Synced 2 products');

    $product = \Drupal::entityTypeManager()->getStorage('shopify_product')->load(1);
    $this->assertEquals(
      "Drupal is content management software. It's used to make many of the websites and applications you use every day. Drupal has great standard features, like easy content authoring, reliable performance, and excellent security. But what sets it apart is its flexibility; modularity is one of its core principles. Its tools help you build the versatile, structured content that dynamic web experiences need.",
      strip_tags($product->get('body_html')->value)
    );

  }

}
