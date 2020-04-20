<?php

namespace Drupal\jcms_admin;

use Embed\Adapters\Adapter;
use Embed\Embed as EmbedLib;

/**
 * Class Embed.
 */
class Embed {

  /**
   * Gets the info from an url.
   */
  public function create(string $uri): Adapter {
    return EmbedLib::create($uri);
  }

}
