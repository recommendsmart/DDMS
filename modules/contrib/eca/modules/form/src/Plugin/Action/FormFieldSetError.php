<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Action to show a validation error message.
 *
 * @Action(
 *   id = "eca_form_field_set_error",
 *   label = @Translation("Form field: set validation error"),
 *   type = "form"
 * )
 */
class FormFieldSetError extends FormFieldValidateActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'message' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('The error message to be shown regards the form field. Supports tokens.'),
      '#default_value' => $this->configuration['message'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['message'] = $form_state->getValue('message');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(): void {
    $this->setError($this->tokenServices->replaceClear($this->configuration['message']));
  }

}
