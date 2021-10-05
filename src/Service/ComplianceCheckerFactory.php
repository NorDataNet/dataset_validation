<?php

namespace Drupal\dataset_validation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ComplianceCheckerFactory.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Create a new fully prepared instance of ComplianceChecker.
   *
   * @return \Drupal\dataset_validation\Service\ComplianceChecker
   */
  public function create() {
    return new ComplianceChecker($this->configFactory);
  }

}
