<?php

namespace Drupal\config_perms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\config_perms\Entity\CustomPermsEntity;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigPermListForm.
 *
 * @package Drupal\config_perms\Form
 */
class ConfigPermListForm extends FormBase {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The route building service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a new ConfigPermListForm.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route building service.
   */
  public function __construct(RouteProviderInterface $route_provider, RouteBuilderInterface $route_builder) {
    $this->routeProvider = $route_provider;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_perm_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['perms'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Permissions'),
      '#description' => '<p>' . $this->t("Please note:") . '</p>' .
      "<ul>
       <li>" . $this->t("Custom permissions only support internal paths") . "</li>\n
       <li>" . $this->t("User 1 still maintains full control") . "</li>\n
       <li>" . $this->t("Remove the permission 'Administer site configuration' from roles you wish to give access to only specified custom site configuration permissions") . "</li>\n
       <li>" . $this->t("Paths can include wildcards. For example, the path 'block/add/*' will grant access to creating a custom block of any type") . "</li>\n
      </ul>",
      '#collapsible' => 1,
      '#collapsed' => 0,
    );

    $perms = CustomPermsEntity::loadMultiple();

    $header = array($this->t('Enabled'), $this->t('Name'), $this->t('Path(s)'));

    $form['perms']['local'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#prefix' => '<div id="config_perms-wrapper">',
      '#suffix' => '</div>',
    );

    foreach ($perms as $key => $perm) {

      $form['perms']['local'][$key] = array('#tree' => TRUE);

      $form['perms']['local'][$key]['status'] = array(
        '#type' => 'checkbox',
        '#default_value' => $perm->status(),
      );

      $form['perms']['local'][$key]['name'] = array(
        '#type' => 'textfield',
        '#default_value' => $perm->label(),
        '#size' => 30,
      );

      $form['perms']['local'][$key]['path'] = array(
        '#type' => 'textarea',
        '#default_value' => $perm->getPath(),
        '#size' => 50,
        '#rows' => 1,
      );

      // Delete link.
      $url_object = Url::fromUri('internal:/admin/structure/custom_perms_entity/' . $perm->id() . '/delete');
      $delete_link = \Drupal::l($this->t('Delete'), $url_object);
      $form['perms']['local'][$key]['delete'] = array(
        '#type' => 'item',
        '#markup' => $delete_link,
      );
      $form['perms']['local'][$key]['id'] = array(
        '#type' => 'hidden',
        '#default_value' => $perm->id(),
      );
    }

    $num_new = $form_state->getValue('num_new');
    if (empty($num_new)) {
      $form_state->setValue('num_new', '0');
    }

    for ($i = 0; $i < $form_state->getValue('num_new'); $i++) {
      $form['perms']['local']['new']['status'] = array(
        '#type' => 'checkbox',
        '#default_value' => '',
      );
      $form['perms']['local']['new']['name'] = array(
        '#type' => 'textfield',
        '#default_value' => '',
        '#size' => 30,
      );

      $form['perms']['local']['new']['path'] = array(
        '#type' => 'textarea',
        '#default_value' => '',
        '#rows' => 2,
        '#size' => 50,
      );

    }

    $form['perms']['add']['status'] = array(
      '#name' => 'status',
      '#id' => 'edit-local-status',
      '#type' => 'submit',
      '#value' => $this->t('Add permission'),
      '#submit' => array('::configPermsAdminFormAddSubmit'),
      '#ajax' => array(
        'callback' => '::configPermsAdminFormAddCallback',
        'wrapper' => 'config_perms-wrapper',
      ),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * Callback for add button.
   */
  public function configPermsAdminFormAddCallback($form, $form_state) {
    return $form['perms']['local'];
  }

  /**
   * Submit for add button.
   */
  public function configPermsAdminFormAddSubmit($form, &$form_state) {
    $form_state->setValue('num_new', $form_state->getValue('num_new') + 1);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $perms = CustomPermsEntity::loadMultiple();

    foreach ($values['local'] as $key => $perm) {

      if (empty($perm['name']) && empty($perm['path']) && $key != 'new') {
        $entity = CustomPermsEntity::load($perm['id']);
        $entity->delete();
      }
      else {
        if (empty($perm['name'])) {
          $form_state->setErrorByName("local][" . $key . "", $this->t("The name cannot be empty."));
        }

        if (empty($perm['path'])) {
          $form_state->setErrorByName("local][" . $key . "", $this->t("The path cannot be empty."));
        }
        if (array_key_exists($this->configPermsGenerateMachineName($perm['name']), $perms) && !isset($perm['id'])) {
          $form_state->setErrorByName("local][" . $key . "", $this->t("A permission with that name already exists."));
        }
        if (!empty($perm['path'])) {
          $paths = $this->configPermsParsePath($perm['path']);
          foreach ($paths as $path) {
            $path = ($path[0] == '/') ? $path : '/' . $path;
            $found_routes = $this->routeProvider->getRoutesByPattern($path);
            if (!$found_routes->count()) {
              $form_state->setErrorByName("local][" . $key . "", $this->t("The path @path is invalid.", array('@path' => $path)));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $perms = CustomPermsEntity::loadMultiple();

    foreach ($values['local'] as $key => $data) {
      // If new permission.
      if ($key == 'new') {
        $entity = CustomPermsEntity::create();
        $entity->set('id', $this->configPermsGenerateMachineName($data['name']));
        $entity->set('label', $data['name']);
        $entity->set('path', $data['path']);
        $entity->set('status', $data['status']);
        $entity->save();
      }
      else {
        // Update || Insert.
        if (!empty($data['name']) && !empty($data['path'])) {
          $entity = $perms[$data['id']];
          $entity->set('label', $data['name']);
          $entity->set('path', $data['path']);
          $entity->set('status', $data['status']);
          $entity->save();
        }
      }
    }

    $this->routeBuilder->rebuild();
    drupal_set_message($this->t('The permissions have been saved.'));
  }

  /**
   * Custom permission paths to array of paths.
   *
   * @param string $path
   *   Path(s) given by the user.
   *
   * @return array|string
   *   Implode paths in array of strings.
   */
  public function configPermsParsePath($path) {
    if (is_array($path)) {
      $string = implode("\n", $path);
      return $string;
    }
    else {
      $path = str_replace(array("\r\n", "\n\r", "\n", "\r"), "\n", $path);
      $parts = explode("\n", $path);
      return $parts;
    }
  }

  /**
   * Generate a machine name given a string.
   */
  public function configPermsGenerateMachineName($string) {
    return strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $string));
  }

}
