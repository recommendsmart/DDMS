<?php

namespace Drupal\entity_reference_link\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference_link' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_link",
 *   label = @Translation("Entity Reference Custom  Link"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceLinkFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * @var int
   * The ID of the entity with this field.
   */
  public $entityId = 0;

  /**
   * Entity Type Manager
   */
  public $entityTypeManager = NULL;

  /**
   * This is set during viewElements(), to provide a static storage of the settings array.
   */
  public $viewSettings = [];

  /**
   * Twig Environment, for rendering template.
   */
  public $twig = NULL;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_generator' => 'route',
      'route_url_options' => [
        'route' => '',
        'route_id_usage' => 'route_parameter',
        'route_id_parameter' => '',
        'route_referencing_id_usage' => 'route_parameter',
        'route_referencing_id_parameter' => '',
      ],
      'custom_url_options' => [
        'template_href' => '/{{ id }}',
      ],
      'display_options' => [
        'link_attributes' => '',
        'link_template' => '{{ label }}',
      ],
      'list_options' => [
        'single_item_type' => 'no_list',
        'list_option_type' => 'separator',
        'list_separator' => ', ',
        'list_element' => 'div',
        'list_classes' => '',
        'list_item_classes' => '',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $form['header'] = [
      '#type' => 'markup',
      '#markup' => '
        <p><strong>Tokens available for settings under "Custom URL Options" and "Display Options"</strong></p>
        <ul>
          <li>{{ id }} ' . $this->t('ID of the referenced entity.') . '</li>
          <li>{{ referencing_id }} ' . $this->t('ID of the referencing entity (i.e. the entity containing this field).') . '</li>
          <li>{{ label }} ' . $this->t('Referenced Entity Label.') . '</li>
        </ul>
      ',
    ];

    // Link generator
    $form['link_generator'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Generator'),
      '#description' => $this->t('Choose the method of link generation. You may use the Drupal routing system, or a custom URL.'),
      '#options' => [
        'route' => $this->t('Drupal Route'),
        'custom' => $this->t('Custom URL'),
      ],
      '#default_value' => $settings['link_generator'],
    ];
    $form['link_generator']['#attributes']['class'][] = 'entity-reference-link--link-generator';

    // Route-based URL options
    $form['route_url_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Route URL options'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          'select.entity-reference-link--link-generator' => ['value' => 'route'],
        ]
      ]
    ];
    $form['route_url_options']['route'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route'),
      '#description' => $this->t('The Drupal route used to construct the link'),
      '#default_value' => $settings['route_url_options']['route'],
    ];
    $form['route_url_options']['route_id_usage'] = [
      '#type' => 'select',
      '#title' => $this->t('Usage of Referenced ID'),
      '#description' => $this->t('You may use the referenced entity ID as either a route parameter or query parameter, or not at all'),
      '#options' => [
        'route_parameter' => $this->t('Route Parameter'),
        'query_parameter' => $this->t('Query Parameter'),
        'none' => $this->t('Do Not Use'),
      ],
      '#default_value' => $settings['route_url_options']['route_id_usage'],
    ];
    $form['route_url_options']['route_id_usage']['#attributes']['class'][] = 'entity-reference-link--route-id-usage';
    $form['route_url_options']['route_id_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Referenced ID Parameter'),
      '#description' => $this->t('Parameter key: "param" would create ?param={{ id }} for a query parameter, for example.'),
      '#default_value' => $settings['route_url_options']['route_id_parameter'],
      '#states' => [
        'invisible' => [
          'select.entity-reference-link--route-id-usage' => ['value' => 'none'],
        ],
        'required' => [
          ['select.entity-reference-link--route-id-usage' => ['value' => 'route_parameter'],],
          ['select.entity-reference-link--route-id-usage' => ['value' => 'query_parameter'],],
        ]
      ]
    ];
    $form['route_url_options']['route_referencing_id_usage'] = [
      '#type' => 'select',
      '#title' => $this->t('Usage of Referencing ID'),
      '#description' => $this->t('You may use the referencing entity ID (i.e. the entity making the reference) as either a route parameter or query parameter, or not at all'),
      '#options' => [
        'route_parameter' => $this->t('Route Parameter'),
        'query_parameter' => $this->t('Query Parameter'),
        'none' => $this->t('Do Not Use'),
      ],
      '#default_value' => $settings['route_url_options']['route_referencing_id_usage'],
    ];
    $form['route_url_options']['route_referencing_id_usage']['#attributes']['class'][] = 'entity-reference-link--route-referencing-id-usage';
    $form['route_url_options']['route_referencing_id_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Referencing ID Parameter'),
      '#description' => $this->t('Parameter key: "param" would create ?param={{ referencing_id }} for a query parameter, for example.'),
      '#default_value' => $settings['route_url_options']['route_referencing_id_parameter'],
      '#states' => [
        'invisible' => [
          'select.entity-reference-link--route-referencing-id-usage' => ['value' => 'none'],
        ],
        'required' => [
          ['select.entity-reference-link--route-referencing-id-usage' => ['value' => 'route_parameter']],
          ['select.entity-reference-link--route-referencing-id-usage' => ['value' => 'query_parameter']],
        ]
      ]
    ];


    // Custom URL Options
    $form['custom_url_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom URL options'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          'select.entity-reference-link--link-generator' => ['value' => 'custom'],
        ]
      ]
    ];
    $form['custom_url_options']['template_href'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template for href'),
      '#description' => $this->t('Create the "href" component of the URL using {{ id }} and {{ referencing_id }}'),
      '#default_value' => $settings['custom_url_options']['template_href'],
    ];

    // Link display options
    $form['display_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Display options'),
      '#open' => FALSE,
    ];
    $form['display_options']['link_template'] = [
      '#type' => 'textarea',
      '#rows' => 1,
      '#title' => $this->t('Link Template'),
      '#description' => $this->t('Template Text for the link'),
      '#default_value' => $settings['display_options']['link_template'],
    ];
    $form['display_options']['link_attributes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Link Attributes'),
      '#description' => $this->t('List HTML attributes, one per line, in a key=value format'),
      '#default_value' => $settings['display_options']['link_attributes'],
    ];

    // List options
    $form['list_options'] = [
      '#type' => 'details',
      '#title' => $this->t('List/Multivalue options'),
      '#open' => FALSE,
    ];
    $form['list_options']['single_item_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Single Item Handling'),
      '#description' => $this->t('You may choose to skip list generation in the case of single items.'),
      '#options' => [
        'no_list' => $this->t('Display the generated link only'),
        'use_list' => $this->t('Use the list logic'),
      ],
      '#default_value' => $settings['list_options']['single_item_type'],
    ];
    $form['list_options']['list_option_type'] = [
      '#type' => 'select',
      '#title' => $this->t('List Type'),
      '#description' => $this->t('Choose the method of listing items. You may use a simple separator, wrap all items in an HTML element, or place items in an ordered or unordered list.'),
      '#options' => [
        'separator' => $this->t('Simple Separator'),
        'element' => $this->t('Wrap items in an element'),
        'ol' => $this->t('Ordered List'),
        'ul' => $this->t('Unordered List'),
      ],
      '#default_value' => $settings['list_options']['list_option_type'],
    ];
    $form['list_options']['list_option_type']['#attributes']['class'][] = 'entity-reference-link--list-option-type';
    $form['list_options']['list_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#description' => $this->t('Text to place between items'),
      '#default_value' => $settings['list_options']['list_separator'],
      '#states' => [
        'visible' => [
          'select.entity-reference-link--list-option-type' => ['value' => 'separator'],
        ],
      ],
    ];
    $form['list_options']['list_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper Element'),
      '#description' => $this->t('Wrap items in this HTML element.'),
      '#options' => [
        'div' => 'div',
        'p' => 'p',
        'span' => 'span',
        'h1' => 'h1',
        'h2' => 'h2',
        'h3' => 'h3',
        'h4' => 'h4',
        'h5' => 'h5',
        'h6' => 'h6',
      ],
      '#default_value' => $settings['list_options']['list_element'],
      '#states' => [
        'visible' => [
          'select.entity-reference-link--list-option-type' => ['value' => 'element'],
        ],
      ],
    ];
    $form['list_options']['list_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List Classes'),
      '#description' => $this->t('Apply these CSS classes to list elements (&lt;ol&gt; or &lt;ul&gt;)'),
      '#default_value' => $settings['list_options']['list_classes'],
      '#states' => [
        'visible' => [
          ['select.entity-reference-link--list-option-type' => ['value' => 'ol']],
          ['select.entity-reference-link--list-option-type' => ['value' => 'ul']],
        ],
      ],
    ];
    $form['list_options']['list_item_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List Classes'),
      '#description' => $this->t('Apply these CSS classes to list item elements (&lt;li&gt;, &lt;div&gt;, etc.)'),
      '#default_value' => $settings['list_options']['list_item_classes'],
      '#states' => [
        'visible' => [
          ['select.entity-reference-link--list-option-type' => ['value' => 'element']],
          ['select.entity-reference-link--list-option-type' => ['value' => 'ol']],
          ['select.entity-reference-link--list-option-type' => ['value' => 'ul']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];
    $summary[] = $this->t('Link Generator: %generator', ['%generator' => $settings['link_generator']]);
    if($settings['link_generator'] === 'route') {
      $summary[] = $this->t('Route: %route', ['%route' => $settings['route_url_options']['route']]);
    }
    else {
      $summary[] = $this->t('href: %href', ['%href' => $settings['custom_url_options']['template_href']]);
    }
    $summary[] = $this->t('Link Text Template: %template', ['%template' => $settings['display_options']['link_template']]);
    $summary[] = $this->t('Single Item Handling: %handling', ['%handling' => $settings['list_options']['single_item_type']]);
    $summary[] = $this->t('List type: %list', ['%list' => $settings['list_options']['list_option_type']]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->entityId = $items->getEntity()->id();
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->viewSettings = $this->getSettings();
    $this->twig = \Drupal::service('twig');
    $elements = [];
    $prepared_items = [];

    // Single item handling: Pass a value that makes us use the "default" logic in the statement below.
    if (count($items) === 1 && $this->viewSettings['list_options']['single_item_type'] === 'no_list') {
      $this->viewSettings['list_options']['list_option_type'] = 'bypass';
    }

    // Prepare the items by creating the link.
    foreach ($items as $delta => $item) {
      /* @var $item \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem */
      $entity_id = $item->getValue()['target_id'];
      // TODO Add hierarchy handling, and return an array instead of the item if necessary.
      switch ($this->viewSettings['list_options']['list_option_type']) {
        default:
          $prepared_items[$entity_id] = $this->viewValue($item);
      }

    }

    // Generate the elements according to the logic.
    switch ($this->viewSettings['list_options']['list_option_type']) {
      case 'separator':
        $elements[] = ['#markup' => implode($this->viewSettings['list_options']['list_separator'], $prepared_items)];
        break;
      case 'element':
        foreach ($prepared_items as $delta => $prepared_item) {
          $elements[$delta] = [
            '#type' => 'html_tag',
            '#tag' => $this->viewSettings['list_options']['list_element'],
            '#value' => $prepared_item,
            '#attributes' => [
              'class' => $this->viewSettings['list_options']['list_item_classes']
            ],
          ];
        }
        break;
      case 'ol':
      case 'ul':
        $list = [
          '#theme' => 'item_list',
          '#list_type' => $this->viewSettings['list_options']['list_option_type'],
          '#items' => [],
          '#attributes' => [
            'class' => $this->viewSettings['list_options']['list_classes']
          ],
        ];
        foreach ($prepared_items as $delta => $prepared_item) {
          $list['#items'][$delta] = [
            '#markup' => $prepared_item,
            '#wrapper_attributes' => [
              'class' => $this->viewSettings['list_options']['list_item_classes']
            ],
          ];
        }
        $elements[] = $list;
        break;
      default:
        foreach ($prepared_items as $delta => $prepared_item) {
          $elements[$delta] = ['#markup' => $prepared_item];
        }
        break;
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
   *   String to be entered into a list.
   *
   */
  protected function viewValue(FieldItemInterface $item) {
    // Load the entity.
    /* @var $item \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem */
    $entity_id = $item->getValue()['target_id'];
    $entity_type = $item->getDataDefinition()->getSettings()['target_type'];
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $replacements = [
      'id' => $entity_id,
      'referencing_id' => $this->entityId,
      'label' => $entity->label(),
    ];

    // Get the link attributes
    $attrs = [];
    $attr_lines = preg_split('/$\R?^/m', $this->viewSettings['display_options']['link_attributes']);
    foreach ($attr_lines as $line) {
      $attr = explode('=', $line);
      if (count($attr) === 2) {
        $attrs[$attr[0]] = $this->twig->renderInline($attr[1], $replacements);
      }
    }

    // Build the link.
    $url = NULL;
    switch($this->viewSettings['link_generator']) {
      case 'route':
        $url = Url::fromRoute($this->viewSettings['route_url_options']['route']);
        $url->setOption('attributes', $attrs);
        switch ($this->viewSettings['route_url_options']['route_id_usage']) {
          case 'route_parameter':
            $url->setRouteParameter($this->viewSettings['route_url_options']['route_id_parameter'], $replacements['id']);
            break;
          case 'query_parameter':
            $url->setOption('query', [$this->viewSettings['route_url_options']['route_id_parameter'] => $replacements['id']]);
            break;
          case 'none':
            break;
        }
        switch ($this->viewSettings['route_url_options']['route_referencing_id_usage']) {
          case 'route_parameter':
            $url->setRouteParameter($this->viewSettings['route_url_options']['route_referencing_id_parameter'], $replacements['referencing_id']);
            break;
          case 'query_parameter':
            // Check for value set above, just in case.
            $q = $url->getOption('query');
            $q = !empty($q) ? $q : [];
            $url->setOption('query', $q + [$this->viewSettings['route_url_options']['route_referencing_id_parameter'] => $replacements['referencing_id']]);
            break;
          case 'none':
            break;
        }
        break;
      case 'custom':
        $url_text = $this->twig->renderInline($this->viewSettings['custom_url_options']['template_href'], $replacements);
        $url = Url::fromUserInput($url_text);
        break;
    }
    // Build the link and return.
    $link_text = $this->twig->renderInline($this->viewSettings['display_options']['link_template'], $replacements);
    $link = Link::fromTextAndUrl($link_text, $url);
    return $link->toString()->getGeneratedLink();
  }

}