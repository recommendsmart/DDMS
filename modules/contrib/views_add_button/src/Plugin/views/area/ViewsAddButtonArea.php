<?php

namespace Drupal\views_add_button\Plugin\views\area;

use Drupal\views\Plugin\views\area\TokenizeAreaPluginBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Defines a views area plugin.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_add_button_area")
 */
class ViewsAddButtonArea extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Build Bundle Type List.
   */
  public function createEntityBundleList() {
    $ret = [];
    $entity_info = \Drupal::entityTypeManager()->getDefinitions();
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    foreach ($entity_info as $type => $info) {
      // Is this a content/front-facing entity?
      if ($info instanceof ContentEntityType) {
        $label = $info->getLabel();
        if ($label instanceof TranslatableMarkup) {
          $label = $label->render();
        }
        $ret[$label] = [];
        $bundles = $bundle_info->getBundleInfo($type);
        foreach ($bundles as $key => $bundle) {
          if ($bundle['label'] instanceof TranslatableMarkup) {
            $ret[$label][$type . '+' . $key] = $bundle['label']->render();
          }
          else {
            $ret[$label][$type . '+' . $key] = $bundle['label'];
          }
        }
      }
    }
    return $ret;
  }

  /**
   * Define the available options.
   *
   * @return array
   *   Array of available options for views_add_button form.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['type'] = ['default' => 'node'];
    $options['context'] = ['default' => ''];
    $options['button_text'] = ['default' => ''];
    $options['button_classes'] = ['default' => ''];
    $options['button_attributes'] = ['default' => ''];
    $options['button_prefix'] = ['default' => ['format' => NULL, 'value' => '']];
    $options['button_suffix'] = ['default' => ['format' => NULL, 'value' => '']];
    $options['query_string'] = ['default' => ''];
    $options['destination'] = ['default' => TRUE];
    $options['tokenize'] = ['default' => FALSE, 'bool' => TRUE];
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['type'] = [
      '#type' => 'select',
      '#title' => t('Entity Type'),
      '#options' => $this->createEntityBundleList(),
      '#empty_option' => '- Select -',
      '#default_value' => $this->options['type'],
      '#weight' => -10,
    ];
    $form['context'] = [
      '#type' => 'textfield',
      '#title' => t('Entity Context'),
      '#description' => t('Certain entities require a special context parameter. Set the context (or relevant 
      token) here. Check the help for the relevant Views Add Button module for further questions.'),
      '#default_value' => $this->options['context'],
      '#weight' => -9,
    ];
    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => t('Button Text for the add button'),
      '#description' => t('Leave empty for the default: "Add [entity_bundle]"'),
      '#default_value' => $this->options['button_text'],
      '#weight' => -7,
    ];
    $form['query_string'] = [
      '#type' => 'textfield',
      '#title' => t('Query string to append to the add link'),
      '#description' => t('Add the query string, without the "?" .'),
      '#default_value' => $this->options['query_string'],
      '#weight' => -6,
    ];
    $form['button_classes'] = [
      '#type' => 'textfield',
      '#title' => t('Button classes for the add link - usually "button" or "btn," with additional styling classes.'),
      '#default_value' => $this->options['button_classes'],
      '#weight' => -5,
    ];
    $form['button_attributes'] = [
      '#type' => 'textarea',
      '#title' => t('Additional Button Attributes'),
      '#description' => t('Add one attribute string per line, without quotes (i.e. name=views_add_button).'),
      '#default_value' => $this->options['button_attributes'],
      '#cols' => 60,
      '#rows' => 2,
      '#weight' => -4,
    ];
    $form['button_prefix'] = [
      '#type' => 'text_format',
      '#title' => t('Prefix HTML'),
      '#description' => t('HTML to inject before the button.'),
      '#cols' => 60,
      '#rows' => 2,
      '#weight' => -3,
      '#default_value' => $this->options['button_prefix']['value'],
    ];
    $form['button_suffix'] = [
      '#type' => 'text_format',
      '#title' => t('Suffix HTML'),
      '#description' => t('HTML to inject after the button.'),
      '#cols' => 60,
      '#rows' => 2,
      '#weight' => -2,
      '#default_value' => $this->options['button_suffix']['value'],
    ];
    $form['destination'] = [
      '#type' => 'checkbox',
      '#title' => t('Include destination parameter?'),
      '#default_value' => $this->options['destination'],
      '#weight' => -1,
    ];
    $this->tokenForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    // Get the entity/bundle type.
    $type = explode('+', $this->options['type'], 2);
    $entity_type = $type[0];
    $bundle = $type[1];

    // Load ViewsAddButton plugin definitions, and find the right one.
    $plugin_manager = \Drupal::service('plugin.manager.views_add_button');
    $plugin_definitions = $plugin_manager->getDefinitions();

    $plugin_class = $plugin_definitions['views_add_button_default']['class'];
    $set_for_bundle = FALSE;
    foreach ($plugin_definitions as $pd) {
      if (!empty($pd['target_entity']) && $pd['target_entity'] === $entity_type) {
        if (!empty($pd['target_bundle'])) {
          $b = $bundle;
          /*
           * In certain cases, like the Group module,
           * we need to extract the true bundle name from a
           * hashed bundle string.
           */
          if (method_exists($pd['class'], 'get_bundle')) {
            $b = $pd['class']::get_bundle($bundle);
          }
          if ($pd['target_bundle'] === $b) {
            $plugin_class = $pd['class'];
            $set_for_bundle = TRUE;
          }
        }
        elseif (!$set_for_bundle) {
          $plugin_class = $pd['class'];
        }
      }
    }

    // Check for entity add access.
    $access = FALSE;
    if (method_exists($plugin_class, 'checkAccess')) {
      $context = $this->options['tokenize'] ? $this->tokenizeValue($this->options['context']) : $this->options['context'];
      $access = $plugin_class::checkAccess($entity_type, $bundle, $context);
    }
    else {
      $entity_manager = \Drupal::entityTypeManager();
      $access_handler = $entity_manager->getAccessControlHandler($entity_type);
      if ($bundle) {
        $access = $access_handler->createAccess($bundle);
      }
      else {
        $access = $access_handler->createAccess();
      }
    }

    if ($access) {
      // Build URL Options.
      $opts = [];
      $dest = Url::fromRoute('<current>');
      $opts['query']['destination'] = $dest->toString();
      $opts['attributes']['class'] = $this->options['tokenize'] ? $this->tokenizeValue($this->options['button_classes']) : $this->options['button_classes'];

      // Build custom attributes.
      if ($this->options['button_attributes']) {
        $attrs = $this->options['button_attributes'] ? $this->tokenizeValue($this->options['button_attributes']) : $this->options['button_attributes'];
        $attr_lines = preg_split('/$\R?^/m', $attrs);
        foreach ($attr_lines as $line) {
          $attr = explode('=', $line);
          if (count($attr) === 2) {
            $opts['attributes'][$attr[0]] = $attr[1];
          }
        }
      }

      // Build query string.
      if ($this->options['query_string']) {
        $q = $this->options['tokenize'] ? $this->tokenizeValue($this->options['query_string']) : $this->options['query_string'];
        if ($q) {
          $qparts = explode('&', $q);
          foreach ($qparts as $part) {
            $p = explode('=', $part);
            if (is_array($p) && count($p) > 1) {
              $opts['query'][$p[0]] = $p[1];
            }
          }
        }
      }

      // Get the url from the plugin and build the link.
      if ($this->options['context']) {
        $context = $this->options['tokenize'] ? $this->tokenizeValue($this->options['context']) : $this->options['context'];
        $url = $plugin_class::generateUrl($entity_type, $bundle, $opts, $context);
      }
      else {
        $url = $plugin_class::generateUrl($entity_type, $bundle, $opts);
      }
      $text = $this->options['button_text'] ? $this->options['button_text'] : 'Add ' . $bundle;
      $text = $this->options['tokenize'] ? $this->tokenizeValue($text) : $text;
      $l = Link::fromTextAndUrl(t($text), $url)->toRenderable();

      if (isset($this->options['button_prefix']) || isset($this->options['button_suffix'])) {
        if (!empty($this->options['button_prefix']['value'])) {
          $prefix = check_markup($this->options['button_prefix']['value'], $this->options['button_prefix']['format']);
          $prefix = $this->options['tokenize'] ? $this->tokenizeValue($prefix) : $prefix;
          $l['#prefix'] = $prefix;
        }
        if (!empty($this->options['button_suffix']['value'])) {
          $suffix = check_markup($this->options['button_suffix']['value'], $this->options['button_suffix']['format']);
          $suffix = $this->options['tokenize'] ? $this->tokenizeValue($suffix) : $suffix;
          $l['#suffix'] = $suffix;
        }
        return $l;
      }

      return $l;
    }
    else {
      return ['#markup' => ''];
    }
  }

}
