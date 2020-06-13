<?php

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Dummy form for testing States API with ajax form.
 *
 * @internal
 */
class AjaxFormsTestStatesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_states';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Ajax callback is used instead.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['num'] = [
      '#type' => 'radios',
      '#title' => 'Number',
      '#options' => ['First' => 'First', 'Second' => 'Second'],
      '#default_value' => 'First',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $form['color'] = [
      '#type' => 'radios',
      '#title' => 'Color',
      '#options' => ['Red' => 'Red', 'Green' => 'Green'],
      '#default_value' => 'red',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $form['textfield1'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 1 (depends on First-Red)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'First'],
          ':input[name="color"]' => ['value' => 'Red'],
        ],
      ],
    ];

    $form['textfield2'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 2 (depends on First-Green)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'First'],
          ':input[name="color"]' => ['value' => 'Green'],
        ],
      ],
    ];

    $form['textfield3'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 3 (depends on Second-Red)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'Second'],
          ':input[name="color"]' => ['value' => 'Red'],
        ],
      ],
    ];

    $form['textfield4'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield 4 (depends on Second-Green)',
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 'Second'],
          ':input[name="color"]' => ['value' => 'Green'],
        ],
      ],
    ];

    $form['data'] = [
      '#markup' => '',
      '#prefix' => '<div id="states-bug-data-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
      '#ajax' => [
        'callback' => '::statesBugData',
        'wrapper' => 'states-bug-data-wrapper',
      ],
    ];

    return $form;
  }

  /**
   * Ajax submit callback.
   */
  public function statesBugData(array $form, FormStateInterface $form_state) {
    $element = $form['data'];
    $element['#markup'] = 'Your choice: ' . $form_state->getValue('num') . ' - ' . $form_state->getValue('color');
    return $element;
  }

}
