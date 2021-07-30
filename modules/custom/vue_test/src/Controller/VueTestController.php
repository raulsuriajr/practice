<?php

namespace Drupal\vue_test\Controller;

class VueTestController {
  public function vueTest() {
    return [
      '#theme' => 'vue_test_template'
    ];
  }
}
