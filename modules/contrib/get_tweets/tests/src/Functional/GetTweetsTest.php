<?php

namespace Drupal\Tests\get_tweets\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class GetTweetsTest.
 *
 * @group get_tweets
 */
class GetTweetsTest extends BrowserTestBase {

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['get_tweets'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer get tweets settings',
    ]);
  }

  /**
   * Test the configuration form.
   */
  public function testAuthError() {

    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/services/get-tweets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals(
      'queries[0][query]',
      $this->config('get_tweets.settings')->get('queries')[0]['query']
    );

    $test_query = 'test';

    $edit = [
      'queries[0][query]' => $test_query,
      'consumer_key' => 'invalid',
      'consumer_secret' => 'invalid',
    ];

    // Post the form.
    $this->drupalPostForm('admin/config/services/get-tweets', $edit, t('Save configuration'));

    $this->assertSession()->pageTextContains(t('Error: "Could not authenticate you." on query: "@query"', [
      '@query' => $test_query,
    ]));

    // Test the new values are not there.
    $this->drupalGet('admin/config/services/get-tweets');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('queries[0][query]', '');
  }

}
