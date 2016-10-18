<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

trait JMCSCheckMarkupTrait {
  public function checkMarkup($html, $format_id = 'basic_html') {
    return check_markup($html, $format_id);
  }
}
