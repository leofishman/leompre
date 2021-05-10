<?php

namespace Drupal\leompre\Batch;

use Drupal\Core\File\FileSystemInterface;

/**
 * Methods for running the CSV import in a batch.
 *
 * @package Drupal\csvimport
 */
class CsvImportBatch {

  /**
   * Handle batch completion.
   *
   *   Creates a new CSV file containing all failed rows if any.
   */
  public static function csvimportImportFinished($success, $results, $operations) {

    $messenger = \Drupal::messenger();

    if (!empty($results['failed_rows'])) {

      $dir = 'public://csvimport';
      if (\Drupal::service('file_system')
        ->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY)) {

        // We validated extension on upload.
        $csv_filename = 'failed_rows-' . basename($results['uploaded_filename']);
        $csv_filepath = $dir . '/' . $csv_filename;

        $targs = [
          ':csv_url'      => file_create_url($csv_filepath),
          '@csv_filename' => $csv_filename,
          '@csv_filepath' => $csv_filepath,
        ];

        if ($handle = fopen($csv_filepath, 'w+')) {

          foreach ($results['failed_rows'] as $failed_row) {
            fputcsv($handle, $failed_row);
          }

          fclose($handle);
          $messenger->addMessage(t('Algunas filas no se pudieron importar. puede descargar un archivo con esos datos: <a href=":csv_url">@csv_filename</a>', $targs), 'error');
        }
        else {
          $messenger->addMessage(t('Algunas filas no se pudieron importar y no pudimos grabar el error CSV en @csv_filepath', $targs), 'error');
        }
      }
      else {
        $messenger->addMessage(t('Algunas filas no se pudieron importar y no pudimos grabar el directorio con el error CSV en @csv_directory'), 'error');
      }
    }

    return t('Finalizo la importacion.');
  }

  /**
   * Remember the uploaded CSV filename.
   *
   * @TODO Is there a better way to pass a value from inception of the batch to
   * the finished function?
   */
  public static function csvimportRememberFilename($filename, &$context) {
    $context['results']['uploaded_filename'] = $filename;
  }

  /**
   * Process a single line.
   */
  public static function csvimportImportLine($line, &$context) {

    $context['results']['rows_imported']++;
  //  $line = array_map('base64_decode', $line);

    // Simply show the import row count.
    $context['message'] = t('Importando fila !c', ['!c' => $context['results']['rows_imported']]);
    // Alternatively, our example CSV happens to have the title in the
    // third column, so we can uncomment this line to display "Importing
    // Blahblah" as each row is parsed.
    //
    // You can comment out the line above if you uncomment this one.
    $context['message'] = t('Importando %title', ['%title' => $line[2]]);

    // In order to slow importing and debug better, we can uncomment
    // this line to make each import slightly slower.
    // @codingStandardsIgnoreStart
    //usleep(2500);
    if ($context['results']['rows_imported'] > 1) {
      self::saveName($line[0]);
    }

    // @codingStandardsIgnoreEnd
    // Convert the line of the CSV file into a new node.
    // @codingStandardsIgnoreStart
    //if ($context['results']['rows_imported'] > 1) { // Skip header line.
    //
    //  /* @var \Drupal\node\NodeInterface $node */
    //  $node = Node::create([
    //    'type'  => 'article',
    //    'title' => $line[2],
    //    'body'  => $line[0],
    //  ]);
    //  $node->save();
    //}
    // @codingStandardsIgnoreEnd

    // If the first two columns in the row are "ROW", "FAILS" then we
    // will add that row to the CSV we'll return to the importing person
    // after the import completes.
  //  if ($line[1] == 'ROW' && $line[2] == 'FAILS') {
  //    $context['results']['failed_rows'][] = $line;
  //  }
  }

  public static function saveName($name){
    $query = \Drupal::database();
    $query->insert('myusers')
      ->fields(['name' => $name])
      ->execute();
    return true;
  }
}