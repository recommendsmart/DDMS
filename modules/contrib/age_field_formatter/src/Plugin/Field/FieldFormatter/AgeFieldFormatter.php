<?php

namespace Drupal\age_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Plugin implementation of the 'age_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "age_field_formatter",
 *   label = @Translation("Age formatter"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class AgeFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['date_format'] = \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $options['age_format'] = TRUE;
    $options['year_suffix'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $age_formats = [
      'birthdate' => $this->t('Date plus Age with label'),
      'birthdate_nolabel' => $this->t('Date with no Age label'),
      'age_only' => $this->t('Age only'),
    ];

    $elements['age_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date/age format'),
      '#options' => $age_formats,
      '#default_value' => $this->getSetting('age_format'),
      '#attributes' => array('class' => array('age-format-select')),
    ];

    $elements['year_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a “years” suffix after the age'),
      '#default_value' => $this->getSetting('year_suffix'),
    ];

    $elements['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/time format'),
      '#description' => $this->t('See <a href="http://php.net/manual/function.date.php" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->getSetting('date_format'),
      '#attributes' => array('class' => array('date-format')),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $setting = $this->getSetting('age_format');
    $year_suffix = $this->getSetting('year_suffix');
    $year_suffix_summary = $this->t('years suffix');

    if ($setting == 'age_only') {
      $format = $this->t('age only');
    } elseif ($setting == 'birthdate_nolabel') {
      $format = $this->t('date (age)');
    } else {
      $format = $this->t('date (age: xx)');
    }

    if ($year_suffix == true) {
      $format = $format . ' + ' . $year_suffix_summary;
    }

    /* @TODO
    $date = new DrupalDateTime();
    $this->setTimeZone($date);
    $summary[] = $date->format($this->getSetting('date_format'), $this->getFormatSettings());
     */

    $summary[] = $this->t('Age format: %format', ['%format' => $format]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {

    $from = new DrupalDateTime($item->date);
    $to = new DrupalDateTime();

    $age = $from->diff($to)->y;

    $agelabel = $this->t('Age');

    $setting = $this->getSetting('age_format');
    $year_suffix = $this->getSetting('year_suffix');

    $format = $this->getSetting('date_format');
    $timezone = $this->getSetting('timezone_override');

    $date_raw = $item->getValue();
    $date = strtotime($date_raw['value']);
    $date_formatted = \Drupal::service('date.formatter')->format($date, 'custom', $format, $timezone != '' ? $timezone : NULL);

    if ($year_suffix == true) {
      $age_suffix = $this->stringTranslation->formatPlural($age, 'year', 'years');
      $age = $age . ' ' . $age_suffix;
    }

    if ($setting == 'birthdate') {
      $age_formatted = $date_formatted ." (".$agelabel.": ". $age .")";
    } elseif ($setting == 'birthdate_nolabel') {
      $age_formatted = $date_formatted ." (". $age .")";
    } else {
      $age_formatted = $age; // We do not force prefix a label to the value.
    }

    return nl2br(Html::escape($age_formatted));
  }

}
