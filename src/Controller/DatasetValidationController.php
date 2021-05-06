<?php
namespace Drupal\dataset_validation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;


class DatasetValidationController extends ControllerBase {

public function outcome() {
$session = \Drupal::request()->getSession();
$outArr = $session->get("result1");
\Drupal::logger('dataset_validation_output')->debug("out array length: " . count($outArr));
$out = implode(" ", $outArr);
$session->remove("result1");
  return [
    '#markup' => '<span><strong><a class="w3-btn w3-black" href="/dataset_validation/form">Test another dataset</a></strong></span><br><br>' . $out,
    '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
  ];
}

}
