<?php

namespace Drupal\dataset_validation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The ComplianceChecker service.
 *
 * @package Drupal\dataset_validation\Service
 */
class ComplianceChecker implements ComplianceCheckerInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;


  /**
   * The message from the compliance checker.
   *
   * @var array
   */
  private $message;

  /**
   * ComplianceChecker constructor.
   *
   * @param \Drupal\Core\ConfigFactoryInterface $config
   *   The configuration settings.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('dataset_validation.settings');
    ;
  }

  /**
   * {@inheritDoc}
   */
  public function checkCompliance(string $filepath, string $filename, string $test): bool {
    // if($this->config->has('compliance_checker_path')) {
    // $bin_path = $this->config->get('compliance_checker_path') . '/';
    // } else { $bin_path = ''; }.
    $bin_path = '';
    $out = NULL;
    $status = NULL;
    exec($bin_path . 'compliance-checker -v -c lenient -f html -o - --test=' . $test . ' ' . $filepath, $out, $status);
    // dpm($out, __FUNCTION__);
    // Remove javascript.
    $out[6] = '';
    $out[7] = '';
    $out[8] = '';
    // Remove javascript.
    $out[9] = '';
    // dpm($out);
    // dpm($status, __FUNCTION__);.
    if ($status === 0) {
      $return_status = TRUE;
      $this->message = $this->createSucessMessage($filename, $test, $out);
    }
    else {
      $return_status = FALSE;
      $this->message = $this->createFailedMessage($filename, $test, $out);
    }

    return $return_status;
  }

  /**
   * Get the message from the compliance checker.
   */
  public function getComplianceMessage(): array {
    return $this->message;
  }

  /**
   * Create the message to display when validation was successful.
   */
  private function createSucessMessage($filename, $test, $out) {
    $details = implode("", $out);
    // $details = check_markup($details, $format_id = 'full_html');
    $details = preg_replace('/(<script.*<\/script>)/', '', $details);
    $message_form = [];
    $message_form = [
      '#type' => 'markup',
      '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16">',
      '#suffix' => '</div>',
      '#markup' => "<span><em>Your dataset <strong>{$filename}</strong> is compliant with <strong>{$test}</strong></em></span>",
      '#allowed_tags' => ['div', 'table', 'tr', 'td', 'strong', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
    ];
    $message_form['details'] = [
      '#type' => 'details',
      '#title' => 'Show details',
    ];
    $message_form['details']['error'] = [
      '#type' => 'markup',
      // '#prefix' => '<div>',
      // '#suffix' => '</div>',
      '#markup' => $details,
      '#allowed_tags' => ['div', 'table', 'tr', 'td', 'strong', 'img',
        'a', 'span', 'h3', 'h4', 'h5', 'br', 'style', 'tbody', 'script',
      ],
    ];
    return $message_form;
  }

  /**
   * Create the message to display when validation failed.
   */
  private function createFailedMessage($filename, $test, $out) {
    $error = implode("", $out);
    // $error = check_markup($error, $format_id = 'full_html');
    $error = preg_replace('/(<script.*<\/script>)/', '', $error);
    $message_form = [];
    $message_form = [
      '#type' => 'markup',
      '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16">',
      '#suffix' => '</div>',
      '#markup' => "<span><em>Your dataset <strong>{$filename}</strong> is <strong>NOT</strong> compliant with <strong>{$test}</strong></em></span>",
      '#allowed_tags' => ['div', 'table', 'tr', 'td', 'strong',
        'img', 'a', 'span', 'h3', 'h4', 'h5', 'br',
      ],
    ];
    $message_form['details'] = [
      '#type' => 'details',
      '#title' => 'Error details',
    ];
    $message_form['details']['error'] = [
      '#type' => 'markup',
      // '#prefix' => '<div>',
      // '#suffix' => '</div>',
      '#markup' => $error,
      '#allowed_tags' => ['div', 'table', 'tr', 'td', 'strong', 'script',
        'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'style', 'tbody',
      ],
    ];
    return $message_form;
  }

}
