<?php

namespace Drupal\dataset_validation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Factory for the dataset validation service.
 *
 * @package Drupal\dataset_validation\Service
 */
class ComplianceCheckerFactory {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Construct the service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config for this service interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Create a new fully prepared instance of ComplianceChecker.
   *
   * @return \Drupal\dataset_validation\Service\ComplianceChecker
   *   Return the ComplianceCheker service object.
   */
  public function create() {
    return new ComplianceChecker($this->configFactory);
  }

}
