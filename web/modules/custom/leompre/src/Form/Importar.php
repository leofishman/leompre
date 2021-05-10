<?php

namespace Drupal\leompre\Form;

use Drupal\Component\Utility\Environment;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use SplFileObject;
use function Sodium\add;

/**
 * Implements form to upload a file and start the batch on form submit.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class Importar extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leompre_csvimport_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attributes'] = [
      'enctype' => 'multipart/form-data',
    ];

    $form['csvfile'] = [
      '#title'            => $this->t('Archivo CSV'),
      '#type'             => 'file',
      '#description'      => ($max_size = Environment::getUploadMaxSize()) ? $this->t('Por restricciones en el server, el <strong>maximumo permitido para archivos es de @max_size</strong>. Archivos mas grandes seran descartados.', ['@max_size' => format_size($max_size)]) : '',
      '#element_validate' => ['::validateFileupload'],
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Importar usuarios'),
    ];

    return $form;
  }

  /**
   * Validate the file upload.
   */
  public static function validateFileupload(&$element, FormStateInterface $form_state, &$complete_form) {

    $validators = [
      'file_validate_extensions' => ['csv CSV'],
    ];

    // @TODO: File_save_upload will probably be deprecated soon as well.
    // @see https://www.drupal.org/node/2244513.
    if ($file = file_save_upload('csvfile', $validators, FALSE, 0, true)) {

      // The file was saved using file_save_upload() and was added to the
      // files table as a temporary file. We'll make a copy and let the
      // garbage collector delete the original upload.
      $csv_dir = 'temporary://csvfile';
      $directory_exists = \Drupal::service('file_system')
        ->prepareDirectory($csv_dir, FileSystemInterface::CREATE_DIRECTORY);

      if ($directory_exists) {
        $destination = $csv_dir . '/' . $file->getFilename();
        if (file_copy($file, $destination, FileSystemInterface::EXISTS_REPLACE)) {
          $form_state->setValue('csvupload', $destination);
        }
        else {
          $form_state->setErrorByName('csvimport', t('Unable to copy upload file to @dest', ['@dest' => $destination]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($csvupload = $form_state->getValue('csvupload')) {

      if ($handle = fopen($csvupload, 'r')) {

        if ($line = fgetcsv($handle, 128)) {
          if ($line[0] != 'name' AND $line[0] != 'nombre') {
            $form_state->setErrorByName('csvfile', $this->t('Perdon, el archivo no tiene el formato esperado y no lo pudimos importar.'));
          }
        }
        fclose($handle);
      }
      else {
        $form_state->setErrorByName('csvfile', $this->t('No pudimos leer el archivo @filepath', ['@filepath' => $csvupload]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($csvupload = $form_state->getValue('csvupload')) {
      $this->addBatch($csvupload);
    }

  }

  public function addBatch($csvupload) {
    $batch_header = [
      'title' => $this->t('Importando CSV ...'),
      'operations' => [],
      'init_message' => $this->t('Comenzando'),
      'progress_message' => $this->t('Procesado @current de @total.'),
      'error_message' => $this->t('Ocurrion un problema durante la importacion'),
      'finished' => '\Drupal\leompre\Batch\CsvImportBatch::csvimportImportFinished',
    ];

    ini_set('auto_detect_line_endings', TRUE);
    $counter = 0;
    $batch[$counter] = $batch_header;
    $file = new SplFileObject($csvupload, 'r');
    while (!$file->eof()) {
      $batch[$counter]['operations'][] = [
        '\Drupal\leompre\Batch\CsvImportBatch::csvimportRememberFilename',
        [$csvupload],
      ];
      $i = 0;
      while ($line = $file->fgetcsv()) {
        $i++;
        $batch[$counter]['operations'][] = [
          '\Drupal\leompre\Batch\CsvImportBatch::csvimportImportLine',
          [$line],
        ];
        if ($i > 15000) {
          $batch[$counter] = $batch_header;
          batch_set($batch[$counter]);
          $i = 0;
          $counter++;
        }
      }
      $batches = 0;
      while ($batches <= $counter) {
        batch_set($batch[$batches]);
        $batches++;
      }

    }

  }

}