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
   *   The path of the file to be checked.
   * @param string $filename
   *   The filename to be checked.
   * @param string $test
   *   The test to be run.
   *
   * @return bool
   *   True if validation successful. False if errors where found.
   */
  public function checkCompliance(string $filepath, string $filename, string $test): bool;

  /**
   * Get the status message of the check.
   *
   * @return array
   *   The message we get from the compliance checker.
   */
  public function getComplianceMessage(): array;

}
