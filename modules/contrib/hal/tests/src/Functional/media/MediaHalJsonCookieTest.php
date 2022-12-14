<?php

namespace Drupal\Tests\hal\Functional\media;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class MediaHalJsonCookieTest extends MediaHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
