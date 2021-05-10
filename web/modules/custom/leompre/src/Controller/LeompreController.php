<?php

namespace Drupal\leompre\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\Query\QueryFactory;


/**
 * Returns responses for leompre routes.
 */
class LeompreController extends ControllerBase
{

  /**
   * Lista nombres.
   */
  public function consulta()
  {
    $config = $this->config('leompre.settings');

    $limit = $config->get('limit');
    $database = \Drupal::database();
    $query = $database->select('myusers', 'mu');
    $query->fields('mu', ['name']);
    $names = $query->extend('Drupal\\Core\\Database\\Query\\PagerSelectExtender')->limit($limit)->execute();

    $header = [$this->t('Nombre')];

    foreach ($names as $name) {
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

  /**
   * Export a CSV of data.
   */
  public function consultaxls()
  {
    $database = \Drupal::database();
    $query = $database->select('myusers', 'mu');
    $query->fields('mu', ['name']);
    $names = $query->execute();

    if ($names) {
      $handle = fopen('php://temp', 'w+');
      $header = [
        'Nombres',
      ];

      fputcsv($handle, $header);
      foreach ($names as $name) {
        fputcsv($handle, [$name->name]);
      }
      rewind($handle);
      $csv_data = stream_get_contents($handle);
      fclose($handle);

      $response = new Response();
      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition', 'attachment; filename="consulta_nombres.csv"');
      $response->setContent($csv_data);

      return $response;
    }
  }
}


