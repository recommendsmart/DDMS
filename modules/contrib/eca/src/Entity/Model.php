<?php

namespace Drupal\eca\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\eca\Plugin\ECA\Modeller\ModellerInterface;

/**
 * Defines the ECA Model entity type.
 *
 * @ConfigEntityType(
 *   id = "eca_model",
 *   label = @Translation("ECA Model"),
 *   label_collection = @Translation("ECA Models"),
 *   label_singular = @Translation("ECA Model"),
 *   label_plural = @Translation("ECA Models"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ECA Model",
 *     plural = "@count ECA Models",
 *   ),
 *   config_prefix = "model",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "filename",
 *     "modeldata"
 *   }
 * )
 */
class Model extends ConfigEntityBase {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    /** @var \Drupal\eca\Entity\Eca $eca */
    if ($eca = $this->entityTypeManager()->getStorage('eca')->load($this->id())) {
      $this->addDependency('config', $eca->getConfigDependencyName());
    }

    return $this;
  }

  /**
   * Set the filename or raw data of the model by the modeller.
   *
   * @param \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface $modeller
   *   The modeller instance which handles the model and can provide either the
   *   filename or the raw data to be stored.
   *
   * @return $this
   */
  public function setData(ModellerInterface $modeller): Model {
    $this
      ->setLabel($modeller->getLabel())
      ->setFilename($modeller->getFilename())
      ->setModeldata($modeller->getModeldata());
    return $this;
  }

  /**
   * Set the label of this model.
   *
   * @return $this
   */
  public function setLabel($label): Model {
    $this->set('label', $label);
    return $this;
  }

  /**
   * Set the external filename of this model.
   *
   * @return $this
   */
  public function setFilename($filename): Model {
    $this->set('filename', $filename);
    return $this;
  }

  /**
   * Get the external filename of this model.
   *
   * @return string
   *   The external filename.
   */
  public function getFilename(): string {
    return $this->get('filename') ?? '';
  }

  /**
   * Set the external filename of this model.
   *
   * @return $this
   */
  public function setModeldata($modeldata): Model {
    $this->set('modeldata', $modeldata);
    return $this;
  }

  /**
   * Get the raw model data of this model.
   *
   * @return string
   *   The raw model data.
   */
  public function getModeldata(): string {
    return $this->get('modeldata') ?? '';
  }

}
