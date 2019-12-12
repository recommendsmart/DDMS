<?php

namespace Drupal\dashboards\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\user\UserDataInterface;
use Drupal\dashboards\Entity\Dashboard;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Form\PreviewToggleTrait;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dashboards\Plugin\SectionStorage\UserDashboardSectionStorage;

/**
 * DashboardLayoutBuilderForm class.
 */
class DashboardLayoutBuilderForm extends EntityForm {
  use PreviewToggleTrait;

  /**
   * LayoutBuiolder Tempstore.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * Section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * User data interface.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('user.data'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, UserDataInterface $user_data, AccountInterface $account) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->userData = $user_data;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'dashboards_layout_builder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL) {
    $form['layout_builder'] = [
      '#type' => 'layout_builder',
      '#section_storage' => $section_storage,
    ];

    $this->sectionStorage = $section_storage;
    $form = parent::buildForm($form, $form_state);

    if ($section_storage instanceof UserDashboardSectionStorage) {
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset to default'),
        '#weight' => 10,
        '#submit' => ['::resetToDefault'],
      ];
    }

    return $form;
  }

  /**
   * Reset to default layout.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function resetToDefault(array $form, FormStateInterface $form_state) {
    $this->userData->delete(
      'dashboards',
      $this->account->id(),
      $this->sectionStorage->getContextValue(Dashboard::CONTEXT_TYPE)->id()
    );
    $form_state->setRedirectUrl(
      Url::fromRoute('entity.dashboard.canonical', [
        'dashboard' => $this->sectionStorage->getContextValue(Dashboard::CONTEXT_TYPE)->id(),
      ])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    // \Drupal\Core\Entity\EntityForm::buildEntity() clones the entity object.
    // Keep it in sync with the one used by the section storage.
    $entity = $this->sectionStorage->getContextValue(Dashboard::CONTEXT_TYPE);
    $entity->isOverriden = TRUE;
    $this->setEntity($this->sectionStorage->getContextValue(Dashboard::CONTEXT_TYPE));
    $entity = parent::buildEntity($form, $form_state);
    $this->sectionStorage->setContextValue(Dashboard::CONTEXT_TYPE, $entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['#attributes']['role'] = 'region';
    $actions['#attributes']['aria-label'] = $this->t('Layout Builder tools');
    $actions['submit']['#value'] = $this->t('Save layout');
    $actions['#weight'] = -1000;

    $actions['discard_changes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Discard changes'),
      '#submit' => ['::redirectOnSubmit'],
      '#redirect' => 'discard_changes',
    ];
    $actions['preview_toggle'] = $this->buildContentPreviewToggle();
    return $actions;
  }

  /**
   * Form submission handler.
   */
  public function redirectOnSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl($form_state->getTriggeringElement()['#redirect']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = $this->sectionStorage->save();
    $this->layoutTempstoreRepository->delete($this->sectionStorage);
    $this->messenger()->addMessage($this->t('The layout has been saved.'));
    $form_state->setRedirectUrl($this->sectionStorage->getRedirectUrl());
    return $return;
  }

  /**
   * Retrieves the section storage object.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage for the current form.
   */
  public function getSectionStorage() {
    return $this->sectionStorage;
  }

}
