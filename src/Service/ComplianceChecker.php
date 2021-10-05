<?php

namespace Drupal\dataset_validation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ComplianceChecker
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


	//Local variable
	private $message; //The message from the compliance checker


  /**
   * ComplianceChecker constructor.
   *
   * @param \Drupal\Core\ConfigFactoryInterface $config
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('dataset_validation.settings');;
  }

  /**
   * {@inheritDoc}
   */
	public function checkCompliance(string $filepath, string $filename, string $test): bool {
		//if($this->config->has('compliance_checker_path')) {
		//	$bin_path = $this->config->get('compliance_checker_path') . '/';
		//} else { $bin_path = ''; }
		$bin_path = '';
		$out = null;
		$status = null;
  	exec($bin_path .'compliance-checker -v -c lenient -f html -o - --test='.$test.' '.$filepath	, $out, $status);
		if($status === 0 ) {
			$return_status = true;
			$this->message = self::createSucessMessage($filename, $test, $out);
		}
		else {
			$return_status = false;
			$this->message = self::createFailedMessage($filename, $test, $out);
		}



  return $return_status;
  }

  public function getComplianceMessage(): array {
	return $this->message;
  }

	private function createSucessMessage($filename, $test, $out) {
		$details = implode("",$out);

		$message_form = [];
		$message_form = [
			'#type' => 'markup',
			'#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16">',
			'#suffix' => '</div>',
			'#markup' => "<span><em>Your dataset <strong>{$filename}</strong> is compliant with <strong>{$test}</strong></em></span>",
			'#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
		];
		$message_form['details'] = [
			'#type' => 'details',
			'#title' => 'Show details',
		];
		$message_form['details']['error'] = [
			'#type' => 'markup',
			//'#prefix' => '<div>',
			//'#suffix' => '</div>',
			'#markup' => $details,
			'#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
		];
	return $message_form;
	}

	private function createFailedMessage($filename, $test, $out) {
		$error = implode("",$out);

		$message_form = [];
		$message_form = [
			'#type' => 'markup',
			'#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16">',
			'#suffix' => '</div>',
			'#markup' => "<span><em>Your dataset <strong>{$filename}</strong> is <strong>NOT</strong> compliant with <strong>{$test}</strong></em></span>",
			'#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
		];
		$message_form['details'] = [
			'#type' => 'details',
			'#title' => 'Error details',
		];
		$message_form['details']['error'] = [
			'#type' => 'markup',
			//'#prefix' => '<div>',
			//'#suffix' => '</div>',
			'#markup' => $error,
			'#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
		];
		return $message_form;
	}
}
