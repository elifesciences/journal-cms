<?php

namespace Drupal\jcms_rest;

use ComposerLocator;

class SchemaPath
{
  public function __toString()
  {
    return ComposerLocator::getPath('elife/api') . '/dist/model';
  }
}
