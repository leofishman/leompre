<?php

namespace Drupal\leompre\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for leompre routes.
 */
class LeompreController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
