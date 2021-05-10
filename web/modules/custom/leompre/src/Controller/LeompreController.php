<?php

namespace Drupal\leompre\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for leompre routes.
 */
class LeompreController extends ControllerBase {

  /**
   * Lista nombres.
   */
  public function consulta() {
    $limit = 10;
    $database = \Drupal::database();
    $query = $database->select('myusers', 'mu');
    $query->fields('mu', ['name']);
    $names = $query->extend('Drupal\\Core\\Database\\Query\\PagerSelectExtender')->limit($limit)->execute();

    $header = [$this->t('Nombre')];

    foreach($names as $name) {
      $row = [$name->name];
      $rows[] = $row;
    }

    $build = [
        'table' => [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
      ],
    ];
    $build['pager'] = array(
        '#type' => 'pager'
    );
    return $build;
  }
}
