<?php

namespace Drupal\dataset_validation\Service;

/**
 * Interface ComplianceCheckerInterface.
 *
 * @package Drupal\dataset_validation\Service
 */
interface ComplianceCheckerInterface {

  /**
   * Check compliance of netCDF file given test.
   *
   * @param string $filepath
   * @param string $filename
   * @param string $test
   *
   * @return bool
   */
  public function checkCompliance(string $filepath, string $filename, string $test): bool;



/**
 * Get the status message of the check
 *
 * @return array
 */
public function getComplianceMessage(): array;

}
