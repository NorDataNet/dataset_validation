<?php

namespace Drupal\dataset_validation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for uplaoding nc file(s) and run the compliance-checker validation.
 *
 * {@inheritdoc}
 */
class DatasetValidationForm extends FormBase {
  /**
   * The archiver plugin manager service.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;
  /**
   * ComplianceChecker.
   *
   * @var \Drupal\dataset_validation\Servoce\ComplianceCheckerInterface
   */
  protected $complianceChecker;

  /**
   * Filesystem service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $filesystem;

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->complianceChecker = $container->get('dataset_validation.compliance_checker');
    $instance->archiverManager = $container->get('plugin.manager.archiver');
    $instance->filesystem = $container->get('file_system');
    $instance->streamWrapper = $container->get('stream_wrapper_manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   *
   *   {@inheritdoc}
   */
  public function getFormId() {
    return 'dataset_validation.form';
  }

  /**
   * Build the validation form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form = array();
    // dpm($form_state);
    // Get supported extensions from ArchiverManager.
    $extensions = $this->archiverManager->getExtensions();

    // Disable caching for this form
    // $form_state->disableCache();
    // Always empty form when rebuild.
    $form = [];

    // Set the current session id.
    if (!$form_state->has('session_id')) {
      $session = $this->getRequest()->getSession();
      $form_state->set('session_id', $session->getId());

      // Check if we have another preset upload location.
      if ($form_state->has('upload_basepath')) {
        $upload_location = $form_state->get('upload_basepath') . md5($form_state->get('session_id'));
      }
      else {
        $upload_location = 'public://dataset_validation_folder/' . md5($form_state->get('session_id'));
      }
      $form_state->set('upload_location', $upload_location);
    }

    /* Create the form layout */
    $form['container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="message-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['container']['message'] = NULL;
    $form['container']['validation_message'] = NULL;

    $form['container']['creation'] = [
      '#type' => 'fieldset',
    // Description of our page.
      '#description' => $this->t('Webform for validation of netCDF files based on the <a href=https://github.com/ioos/compliance-checker>IOOS compliance checker </a>'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['container']['creation']['test'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the test you want to run'),
      "#options" => [
        "cf" => "CF",
        "acdd" => "ACDD",
      ],
      '#default_value' => ['cf', 'acdd'],
      // '#attributes' => ['checked' => 'unchecked'],
      '#required' => TRUE,
      /*'#states' => [
      'invisible' => [
      ':input[name="cf"]' => [ 'checked' => 'cf'],
      ],
      ],*/
    ];

    $form['container']['creation']['cfversion'] = [
      '#title' => $this->t('Select the CF convesion version'),
      '#type' => 'radios',
    // '#required' => true,
      '#options' => [
        'cf:1.6' => $this->t('CF-1.6'),
        'cf:1.7' => $this->t('CF-1.7'),
        'cf:1.8' => $this->t('CF-1.8'),
      ],
      '#default_value' => 'cf:1.6',
      '#states' => [
        'invisible' => [
          ':input[name="test[cf]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="test[cf]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['container']['creation']['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Your File'),
      '#description' => $this->t('You can upload a single netCDF (.nc) file, or an archive with multiple netCDF files (@extensions) Maximum filesize is 1500M. You need to upload a bigger file, take contact with the website support directly.', ['@extensions' => $extensions]),
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'nc ' . $extensions,
        ],
        'FileSizeLimit' => [
          'fileLimit' => 15000000000,
        ],
      ],

    // IMPORTANT for allowing file upload:
    // this works only when changing the /etc/php5/apache2/php.ini.
    // post_max_size and filesize in apache to 200M.
    //    'file_validate_size' => [1500000000],
    //  ],
      '#upload_location' => $form_state->get('upload_location'),
    ];

    // dpm($form_state->get('upload_location'));.
    $form['container']['creation']['actions'] = [
      '#type' => 'actions',
    ];
    $form['container']['creation']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Validate'),
      '#submit' => ['::validate'],
      '#ajax' => [
        'callback' => '::validateCallback',
        'wrapper' => 'message-wrapper',
    // 'disable-refocus' => true,
      ],
    ];

    $form['#attached']['library'][] = 'dataset_validation/style';

    if ($form_state->getValue('reset-upload-field')) {
      $form['container']['creation']['file']['#file'] = FALSE;
      $form['container']['creation']['file']['filename'] = [];
      $form['container']['creation']['file']['#value']['fid'] = 0;
    }
    // dpm($form, __FUNCTION__);
    // dpm($form_state, __FUNCTION__);.
    return $form;
  }

  /**
   * Validate form ajax callback. Add message.
   */
  public function validateCallback(array &$form, FormStateInterface $form_state) {
    $message = $form_state->get('validation_message');
    // dpm($message);
    /*  $form['container']['creation']['file']['#file'] = false;
    $form['container']['creation']['file']['filename'] = [];
    $form['container']['creation']['file']['#value']['fid'] = 0; */
    // $form['message']['result'] = [];

    $form['container']['message']['cf'] = $message[0] ?? NULL;
    $form['container']['message']['acdd'] = $message[1] ?? NULL;
    // dpm($form, __FUNCTION__);.
    return $form;

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    /*
    $renderer = \Drupal::service('renderer');
    $rendered_message = $renderer->render($message);
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#message-wrapper', $message));
    return $response;
     */
  }

  /**
   * Validate the uploaded file.
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    // Get the form values.
    $values = $form_state->getValues();
    // dpm($values);
    // Get file id of uploaded file and then get the real os filepath.
    $file_id = $values['file'][0];
    $form_state->set('fid', $file_id);
    // $file = File::load($file_id);
    $file = $this->entityTypeManager->getStorage('file')->load($file_id);
    $file->setTemporary();
    $file->save();
    $form_state->set('upload_fid', $file->id());
    $uri = $file->getFileUri();
    $stream_wrapper_manager = $this->streamWrapper->getViaUri($uri);
    $file_path = $stream_wrapper_manager->realpath();
    $filename = $file->getFilename();
    $form_state->set('filename', $filename);
    $mime_type = $file->getMimeType();
    // dpm($mime_type);
    $tests = NULL;

    if ($form_state->has('tests')) {
      // dpm("getting tests form form state");.
      $tests = $form_state->get('tests');
      // dpm($tests);
    }
    else {
      $tests = $values['test'];
      if ($tests['cf'] !== 0) {
        if ($form_state->has('cfversion')) {
          $tests['cf'] = $form_state->get('cfversion');
        }
        else {
          $tests['cf'] = $values['cfversion'];
        }
      }
    }

    // dpm($form['container']['message']);
    // dpm($form_state, __FUNCTION__);
    // dpm($tests, __FUNCTION__);
    // get the cf version:
    $options = [];
    // Absolute system filepath.
    $options['filepath'] = $this->filesystem->realpath($uri);
    // dpm($options);
    // process single netCDF file.
    if ($mime_type === 'application/x-netcdf') {
      // dpm('processing single: ' . $mime_type);.
      $this->processSingle($form, $form_state, $tests, $file_path, $filename, $file);
    }
    // Process archive of netCDF files.
    else {
      // dpm('processing archived files: '. $mime_type);.
      $this->processArchive($form, $form_state, $tests, $file_path, $filename, $file, $options);
    }
    if (!$form_state->has('page')) {
      // $file->delete();
      // $filesystem = \Drupal::service('file_system');
      $this->filesystem->deleteRecursive($form_state->get('upload_location'));
    }
  }

  /**
   * Clean the uploaded file once the validation is complete.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form['message']['result'] = [];

    if (!$form_state->has('page')) {
      $file_id = $form_state->get('fid');
      $file = $this->entityTypeManager->getStorage('file')->load($file_id);
      // File::load($file_id);
      $file->delete();
    }
    return $form;
  }

  /**
   * Function for processing single netCDF file.
   */
  private function processSingle(array &$form, FormStateInterface $form_state, $tests, $file_path, $filename, $file) {
    $message = [];
    // dpm($tests);
    // Loop over tests and check the compliance.
    $int_status = 0;
    if ($tests !== NULL) {
      foreach ($tests as $_ => $value) {
        if ($value !== 0) {
          // dpm("doing test: " . $key);.
          $status = $this->complianceChecker->checkCompliance($file_path, $filename, $value);
          // dpm($status, __FUNCTION__);.
          $message[] = $this->complianceChecker->getComplianceMessage();

          if (!$status) {
            $int_status++;
          }
        }
      }
    }
    $form_state->set('int_status', $int_status);
    $form_state->set('validation_message', $message);

    // Delete the file:
    if (!$form_state->has('keep_file')) {
      $file->delete();
      // $filesystem = \Drupal::service('file_system');
      $this->filesystem->deleteRecursive($form_state->get('upload_location'));
    }
    // $form_state->cleanValues();
    $form_state->set('file_path', $file_path);
    $form_state->setValue('reset-upload-field', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Function for processing archive of netCDF files.
   */
  private function processArchive(array &$form, FormStateInterface $form_state, $tests, $file_path, $filename, $file, $options) {
    $message = [];
    $archived_files = [];
    // Get the archiver instance for the given file.
    $archiver = $this->archiverManager->getInstance($options);
    // dpm($archiver);
    if ($archiver == NULL) {
      $archive_message = [
        '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-border-red w3-pale-red w3-container w3-padding-16">',
        '#suffix' => '</div>',
        '#markup' => "<span><em><strong>Error: </strong> Could not process archive <strong>{$filename}</strong></em></span>",
        '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style', 'strong',
          'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span',
        ],
      ];

      // $form_state->setRebuild();
      // return;.
    }
    else {
      $archive_message = [
        '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-padding-16">',
        '#suffix' => '</div>',
        '#markup' => "<span><em>Showing validation result(s) for dataset(s) in archvie <strong>{$filename}</strong></em></span>",
        '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style', 'strong',
          'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span',
        ],
      ];
      $archived_files = $archiver->listContents();
      // Get list of files in archive.
      $form_state->set('archived_files', $archived_files);

      // Extract the files.
      $archiver->extract($this->filesystem->realpath($form_state->get('upload_location')));

      // Add aggregation flag if more than one file in archive.
      if (count($archived_files) > 1) {
        $form_state->set('aggregate', TRUE);
      }
    }
    $message[] = $archive_message;

    // dpm($archived_files);
    $agg_files = [];
    // Loop over tests and check the compliance.
    $int_status = 0;

    foreach ($archived_files as $f) {
      $uri = $form_state->get('upload_location') . '/' . $f;
      $filepath = $this->filesystem->realpath($uri);
      // dpm($filepath);
      $path_info = pathinfo($filepath);
      // dpm($path_info);
      if (isset($path_info['extension'])) {
        // \Drupal::logger('archiver')->debug($filepath . ' : ' . $ext);
        $ext = $path_info['extension'];

        if ($tests !== NULL && ($ext === 'nc')) {
          // \Drupal::logger('archiver')->debug($filepath . ' : ' . $ext);
          $agg_files[] = $filepath;
          foreach ($tests as $_ => $value) {
            if ($value !== 0) {
              // dpm("doing test: " . $key);.
              $status = $this->complianceChecker->checkCompliance($filepath, $f, $value);
              $message[] = $this->complianceChecker->getComplianceMessage();

              if (!$status) {
                $int_status++;
              }
            }
          }
        }
      }
    }
    $form_state->set('agg_files', $agg_files);
    $form_state->set('int_status', $int_status);
    $form_state->set('validation_message', $message);

    // Delete the file:
    if (!$form_state->has('keep_file')) {
      $file->delete();
      $this->filesystem->deleteRecursive($form_state->get('upload_location'));
    }
    // $form_state->cleanValues();
    $form_state->setValue('reset-upload-field', TRUE);
    $form_state->setRebuild();
  }

}
