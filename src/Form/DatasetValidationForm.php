<?php
/*
 * @file
 * Contains \Drupal\dataset_validation\DatasetValidationForm
 *
 * This form will upload a MMD file and create landig page with doi
 *
 */

namespace Drupal\dataset_validation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystem;
use Drupal\dataset_validation\Service\ComplianceCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Archiver\ArchiverInterface;

/*
 * {@inheritdoc}
 * Form class for the bokeh init form
 */
class DatasetValidationForm extends FormBase
{
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
     * constructor
     *
     * @param \Drupal\dataset_validation\Service\ComplianceCheckerInterface
     *
     */
    //public function __construct(ComplianceCheckerInterface $compliance_checker) {
    // $this->complianceChecker = $compliance_checker;
    //}

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->complianceChecker = $container->get('dataset_validation.compliance_checker');
        $instance->archiverManager = $container->get('plugin.manager.archiver');

        return $instance;
    }

    /*
     * Returns a unique string identifying the form.
     *
     * The returned ID should be a unique string that can be a valid PHP function
     * name, since it's used in hook implementation names such as
     * hook_form_FORM_ID_alter().
     *
     * @return string
     *   The unique string identifying the form.
     *
     * {@inheritdoc}
     */


    public function getFormId()
    {
        return 'dataset_validation.form';
    }

    /**
     * @param $form
     * @param $form_state
     *
     * @return mixed
     *
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {


    //Get supported extensions from ArchiverManager.
        $extensions = $this->archiverManager->getExtensions();

        //Disable caching for this form
        //$form_state->disableCache();

        //Always empty form when rebuild.
        $form = array();

        //Set the current session id
        if (!$form_state->has('session_id')) {
            $session = \Drupal::request()->getSession();
            $form_state->set('session_id', $session->getId());

            //Check if we have another preset upload location
            if ($form_state->has('upload_basepath')) {
                $upload_location = $form_state->get('upload_basepath') . md5($form_state->get('session_id'));
            } else {
                $upload_location = 'public://dataset_validation_folder/'. md5($form_state->get('session_id'));
            }
            $form_state->set('upload_location', $upload_location);
        }

        /**
        * Build the form
        */
        $form['container'] = [
    '#type' => 'container',
    '#prefix' => '<div id="message-wrapper">',
    '#suffix' => '</div>',
  ];
        $form['container']['message'] = [];
        $form['container']['validation_message'] = [];

        $form['container']['creation'] = [
   '#type' => 'fieldset',
   '#description' => $this->t('Webform for validation of netCDF files based on the <a href=https://github.com/ioos/compliance-checker>IOOS compliance checker </a> '), // Description of our page
   '#collapsible' => true,
   '#collapsed' => false,
 ];
        $form['container']['creation']['test'] = [
     '#type' => 'checkboxes',
   '#title' => $this->t('Select the test you want to run'),
     "#options" => [
       "cf:1.6" => "CF-1.6",
       "acdd" => "ACDD",
     ],
     //'#default_value' => $form_state->getValue('test'),
   '#attributes' => ['checked' => 'unchecked'],
   '#required' => true,
 ];

        $form['container']['creation']['file'] = [
   '#type' => 'managed_file',
   '#title' => $this->t('Upload Your File'),
   '#description' => t('You can upload a single netCDF (.nc) file, or an archive with multiple netCDF files (' .$extensions. ') Maximum filesize is 1500M. You need to upload a bigger file, take contact with the website support directly.'),
   '#required' => true,
   '#multiple' => false,
   '#upload_validators' => [
      'file_validate_extensions' =>  ['nc ' . $extensions],
 // IMPORTANT for allowing file upload:
 // this works only when changing the /etc/php5/apache2/php.ini post_max_size and filesize in apache to 200M
      'file_validate_size' => array(1500 * 1024 * 1024),
    ],
   '#upload_location' => $form_state->get('upload_location'),
 ];

        //dpm($form_state->get('upload_location'));

        $form['container']['creation']['actions'] = [
   '#type' => 'actions',
 ];
        $form['container']['creation']['actions']['submit'] = [
  '#type' => 'submit',
  '#value' => t('Validate'),
  '#submit' => ['::validate'],
  '#ajax' => [
    'callback' => '::validateCallback',
    'wrapper' =>'message-wrapper',
    //'disable-refocus' => true,
    ],
];


        // $form['container']['message'] = [
        //   '#type' => 'container',
        //  ];
        //$form['message']['result'] = [];

        $form['#attached']['library'][] = 'dataset_validation/style';
        //$form['#submit'][] = 'dataset_validation_submit';
        return $form;
    }

    /*
     * {@inheritdoc}
     * TODO: Impletment form validation here
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        return parent::validateForm($form, $form_state);
    }


    /**
     * Validate form ajax callback. Add message
     */
    public function validateCallback(array &$form, FormStateInterface $form_state)
    {
        $message = $form_state->get('validation_message');
        //dpm($message);
        $form['container']['creation']['file']['#file'] = false;
        $form['container']['creation']['file']['filename'] = [];
        $form['container']['creation']['file']['#value']['fid'] = 0;
        //$form['message']['result'] = [];
        $form['container']['message'] = $message;
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
     * Validate the uploaded file
     */
    public function validate(array &$form, FormStateInterface $form_state)
    {
        //Get the form values
        $values = $form_state->getValues();

        //Get file id of uploaded file and then get the real os filepath
        $file_id = $values['file'][0];
        $form_state->set('fid', $file_id);
        $file = File::load($file_id);
        $file->setTemporary();
        $file->save();
        $form_state->set('upload_fid', $file->id());
        $uri = $file->getFileUri();
        $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
        $file_path = $stream_wrapper_manager->realpath();
        $filename = $file->getFilename();
        $form_state->set('filename', $filename);
        $mime_type = $file->getMimeType();
        // dpm($mime_type);
        $tests = null;

        if ($form_state->has('tests')) {
            $tests = $form_state->get('tests');
        } else {
            $tests = $values['test'];
        }
        $options = array();
        $options['filepath'] = \Drupal::service('file_system')->realpath($uri); //Absolute system filepath
        //dpm($options);
        //process single netCDF file
        if ($mime_type === 'application/x-netcdf') {
            //dpm('processing single: ' . $mime_type);
            self::processSingle($form, $form_state, $tests, $file_path, $filename, $file);
        }
        //Process archive of netCDF files
        else {
            //dpm('processing archived files: '. $mime_type);
            self::processArchive($form, $form_state, $tests, $file_path, $filename, $file, $options);
        }
    }
    /*
   * {@inheritdoc}
   * Redirect init form to plot

   */

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $form['message']['result'] = [];

        return $form;
    }
    /**
     * Function for processing single netCDF file
     */

    private function processSingle(array &$form, FormStateInterface $form_state, $tests, $file_path, $filename, $file)
    {
        $message = [];
        //Loop over tests and check the compliance.
        $int_status = 0;
        if ($tests !== null) {
            foreach ($tests as $key => $value) {
                if ($value !== 0) {
                    //dpm("doing test: " . $key);
                    $status = $this->complianceChecker->checkCompliance($file_path, $filename, $key);
                    $message[] = $this->complianceChecker->getComplianceMessage();

                    if (!$status) {
                        $int_status++;
                    }
                }
            }
        }
        $form_state->set('int_status', $int_status);
        $form_state->set('validation_message', $message);

        //Delete the file:
        if (!$form_state->has('keep_file')) {
            $file->delete();
            $filesystem = \Drupal::service('file_system');
            $filesystem->deleteRecursive($form_state->get('upload_location'));
        }
        //$form_state->cleanValues();
        $form_state->set('file_path', $file_path);
        $form_state->setRebuild();
    }

    /**
     * Function for processing archive of netCDF files
     */

    private function processArchive(array &$form, FormStateInterface $form_state, $tests, $file_path, $filename, $file, $options)
    {
        $message = [];
        $archived_files = [];
        //Add special message when processing files in archive



        //Get the archiver instance for the given file
        $archiver = $this->archiverManager->getInstance($options);
        //dpm($archiver);
        if ($archiver == null) {
            $archive_message = [
          '#type' => 'markup',
          '#prefix' => '<div class="w3-panel w3-leftbar w3-border-red w3-pale-red w3-container w3-padding-16">',
          '#suffix' => '</div>',
          '#markup' => "<span><em><strong>Errror: </strong> Could not process archive <strong>{$filename}</strong></em></span>",
          '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
        ];

        //$form_state->setRebuild();

          //return;
        } else {
            $archive_message = [
        '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-padding-16">',
        '#suffix' => '</div>',
        '#markup' => "<span><em>Showing validation result(s) for dataset(s) in archvie <strong>{$filename}</strong></em></span>",
        '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
      ];
            $archived_files = $archiver->listContents();
            //\Drupal::logger('dataset_validation_archiver')->debug('<pre><code>' . print_r($archived_files, true) . '</code></pre>');
            //Get list of files in archive
            $form_state->set('archived_files', $archived_files);

            //Extract the files
            $archiver->extract(\Drupal::service('file_system')->realpath($form_state->get('upload_location')));

            //Add aggregation flag if more than one file in archive.
            if (count($archived_files) > 1) {
                $form_state->set('aggregate', true);
            }
        }
        $message[] = $archive_message;



        //dpm($archived_files);
        $agg_files = [];
        //Loop over tests and check the compliance.
        $int_status = 0;

        foreach ($archived_files as $f) {
            $uri = $form_state->get('upload_location') .'/' .$f;
            $filepath = \Drupal::service('file_system')->realpath($uri);
            $path_info = pathinfo($filepath);
            $ext = $path_info['extension'];
            if (!is_null($ext)) {
                //\Drupal::logger('archiver')->debug($filepath . ' : ' . $ext);



                if ($tests !== null && ($ext === 'nc')) {
                    //\Drupal::logger('archiver')->debug($filepath . ' : ' . $ext);
                    $agg_files[] = $filepath;
                    foreach ($tests as $key => $value) {
                        if ($value !== 0) {
                            //dpm("doing test: " . $key);
                            $status = $this->complianceChecker->checkCompliance($filepath, $f, $key);
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

        //Delete the file:
        if (!$form_state->has('keep_file')) {
            $file->delete();
            $filesystem = \Drupal::service('file_system');
            $filesystem->deleteRecursive($form_state->get('upload_location'));
        }
        //$form_state->cleanValues();

        $form_state->setRebuild();
    }
}
