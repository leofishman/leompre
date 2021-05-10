<?php

namespace Drupal\leompre\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements the user register form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class UserRegister extends FormBase {

  /**
   * Counter keeping track of the sequence of method invocation.
   *
   * @var int
   */
  protected static $sequenceCounter = 0;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<div class="result_message">' . $this->t('Add an entry to the myusers table.') . '</div>',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre'),
      '#required' => true,

    ];
    
    $form['add']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#ajax' => [
          'callback' => '::ajaxSubmit',
          'wrapper' => 'message-wrapper',
      ],
    ];
    

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_leompre_user_register_form';
  }


  public function nameExist(string $name) {
  
    $query = \Drupal::database()->query('SELECT * from myusers where
        name = :name', array(
        ':name' => $name,
        )
    );
    $data = $query->fetchField();   
  
    return $data ? true : false;
 }

 public function saveName($name){
    $query = \Drupal::database();
    $query->insert('myusers')
            ->fields(['name' => $name])
            ->execute();
    return true;
 }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    if(strlen($name) < 5) {
        $form_state->setErrorByName(
            'name',
            $this->t('El nombre tiene que tener minimo de 5 letras')
        );
    }
    if(!preg_match("/^[a-zA-Z]+$/", $name) == 1) {
        $form_state->setErrorByName(
            'name',
            $this->t('El nombre solo puede tener letras de la A a la Z')
        );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

}

  /**
   * Implements ajax submit callback.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   */
   public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $response = new AjaxResponse();
    if(strlen($name) < 5) {
        $response->addCommand(
        new HtmlCommand(
            '.result_message',
            '<div class="top_message">' . $this->t('El nombre tiene que tener minimo de 5 letras')
            )
        );
        return $response; 
    }
    if(!preg_match("/^[a-zA-Z]+$/", $name) == 1) {
        $response->addCommand(
        new HtmlCommand(
            '.result_message',
            '<div class="top_message">' . $this->t('El nombre solo puede tener letras de la A a la Z')
          )
        );
        return $response;
    }
    if ($this->nameExist($name)) {
        $response->addCommand(
        new HtmlCommand(
            '.result_message',
            '<div class="top_message">' . $this->t('El nombre ya existe')
          )
        );
        return $response;       
    }

    $this->saveName($name); 
    $response->addCommand(    
        
        new HtmlCommand(
            '.result_message',
            '<div class="top_message">' . $this->t('El nombre a sido agregado')
            )
        );       

    return $response;
  }
}
