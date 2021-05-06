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

/*
 * {@inheritdoc}
 * Form class for the bokeh init form
 */
class DatasetValidationForm extends FormBase {
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
  public function getFormId() {
    return 'dataset_validation_form';
  }

 /*
  * @param $form
  * @param $form_state
  *
  * @return mixed
  *
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

    //$config = \Drupal::config('landing_page_creator.configuration');

    $form_state->disableCache();

    $session = \Drupal::request()->getSession();
    $message = $session->get("result1");

  /**
  * Build the form
  */
  $form['creation'] = array(
   '#type' => 'fieldset',
   '#description' => t('Webform for validation of netCDF files based on the <a href=https://github.com/ioos/compliance-checker>IOOS compliance checker </a> '), // Description of our page
   '#collapsible' => TRUE,
   '#collapsed' => FALSE,
 );
  $form['creation']['test'] = array(
   '#type' => 'checkboxes',
   '#title' => t('Select the test you want to run'),
     "#options" => array(
       "cf:1.6" => t("CF-1.6"),
       "acdd" => t("ACDD"),
     ),
   '#attributes' => array('checked' => 'unchecked'),
   '#required' => TRUE,
 );

  $form['creation']['file'] = [
   '#type' => 'managed_file',
   '#title' => t('Upload Your File'),
   '#description' => t('You can only upload a single netCDF file with ".nc" extension, with a maximum size of 1500M. You need to upload a bigger file, take contact with the website support directly.'),
   '#required' => TRUE,
   '#multiple' => FALSE,
   '#upload_validators' => [
      'file_validate_extensions' =>  ['nc'],
 // IMPORTANT for allowing file upload:
 // this works only when changing the /etc/php5/apache2/php.ini post_max_size and filesize in apache to 200M
      'file_validate_size' => array(1500 * 1024 * 1024),
    ],
   '#upload_location' => 'public://dataset_validation_folder',
 ];
if($message != null) {
 $form['message'] = [
   '#type' => 'markup',
   '#markup' => $message,
 ];
$session->remove("result1");
}
  $form['submit'] = array(
   '#type' => 'submit',
   '#value' => t('Submit'),
 //  '#attributes' => array('onclick' => 'this.form.target="_blank";return true;'),
  );


  //$form['#submit'][] = 'dataset_validation_submit';
  return $form;
  }

  /*
   * {@inheritdoc}
   * TODO: Impletment form validation here
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
      //Get the form values
  /*    $values = $form_state->getValues();
      //Get the selected tests to run
      $cf = $values['test']['cf:1.6'];
      $acdd = $values['test']['acdd'];

      if($cf == 0 && $acdd == 0)  {

        $form_state->setErrorByName('test', $this->t('You must select at least one test to run'));

      } */
    }


 	/*
   * {@inheritdoc}
   * Redirect init form to plot
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * Submit the form and do some actions
     */


     //Get the form values
     $values = $form_state->getValues();

     //Get file id of uploaded file and then get the real os filepath
     $fid = $values['file'][0];
     $file = File::load($fid);
     $furi = $file->getFileUri();
     $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($furi);
     $file_path = $stream_wrapper_manager->realpath();
     $filename = $file->getFilename();
     //Get the selected tests to run
     $test1 = $values['test']['cf:1.6'];
     $test2 = $values['test']['acdd'];
     \Drupal::logger('dataset_validation')->debug("test1 :" . $test1);
     \Drupal::logger('dataset_validation')->debug("test2 :" . $test2);
     $path = explode("://", $furi)[1];
     //create output files for the checker
     $name = explode(".nc", $filename)[0];
     $name_out_cf = $name.'_cf.html';
     $name_out_acdd = $name.'_acdd.html';

     $fdir = drupal_realpath('public://');

     \Drupal::logger('dataset_validation')->debug("extracted fdir : " .$fdir);
     \Drupal::logger('dataset_validation')->debug("extracted path : " .$path);
     $out = null;
     $out1 = null;
     $out2 = null;
     $status = null;
     $status1 = null;
     $status2 = null;


     //$ex_out_cf = $fdir.'/dataset_validation_folder/'.$name_out_cf;
     //$ex_out_acdd = $fdir.'/dataset_validation_folder/'.$name_out_acdd;
     $ex_out_cf = '/tmp/'.$name_out_cf;
     $ex_out_acdd = '/tmp/'.$name_out_acdd;
     //\Drupal::logger('dataset_validation')->debug("outfile CF: " . $ex_out_cf);
     //\Drupal::logger('dataset_validation')->debug("outfile ACDD: " . $ex_out_acdd);

     if($test1 =='cf:1.6' AND $test2 =='0'){
       \Drupal::logger('dataset_validation')->debug("running CF compliance check");
        \Drupal::messenger()->addMessage(t("You are testing you dataset \"".$filename."\" against CF-1.6 convention"), 'status');
        //exec('compliance-checker -v -c lenient -f html -o '.$ex_out_cf.' --test='.$test1.' '.$fdir.'/'.$path, $out, $status);
        exec('compliance-checker -v -c lenient -f html -o - --test='.$test1.' '.$fdir.'/'.$path, $out, $status);
        //\Drupal::logger('dataset_validation')->debug('compliance-checker -v -c lenient -f text -o - --test='.$test1.' '.$fdir.'/'.$path);
        \Drupal::logger('dataset_validation')->debug("got CF status: " . $status);
        $message = $out;
        //$message = file_get_contents($ex_out_cf);
     }elseif($test1 =='0' AND $test2 =='acdd'){
       \Drupal::logger('dataset_validation')->debug("running ACDD compliance check");
        \Drupal::messenger()->addMessage(t("You are testing you dataset \"".$filename."\" against ACDD convention"), 'status');
        exec('compliance-checker -v -c lenient -f html -o - --test='.$test1.' '.$fdir.'/'.$path, $out, $status);
        \Drupal::logger('dataset_validation')->debug("got ACDD status: " . $status);
        //$message = file_get_contents($ex_out_acdd);
     }elseif($test1 =='cf:1.6' AND $test2 =='acdd'){
       \Drupal::logger('dataset_validation')->debug("running CF  and ACDD compliance checks");
       //\Drupal::logger('dataset_validation')->debug('compliance-checker -v -c lenient --format=html --output='.$ex_out_cf.' --test='.$test1.' '.$fdir.'/'.$path);
        \Drupal::messenger()->addMessage(t("You are testing you dataset \"".$filename."\" against CF-1.6 and ACDD convention"), 'status');
        exec('compliance-checker -v -c lenient -f html -o - --test='.$test1.' '.$fdir.'/'.$path, $out1, $status1);
        exec('compliance-checker -v -c lenient -f html -o - --test='.$test2.' '.$fdir.'/'.$path, $out2, $status2);
        $status = $status1 + $status2;
        \Drupal::logger('dataset_validation')->debug("got status: " . $status);
         $out = array_merge($out1, $out2);
        //put together the html outputs
        //$message = file_get_contents($ex_out_cf).file_get_contents($ex_out_acdd);
        //exec('compliance-checker -v --test='.$test1.' --test='.$test2.' '.$fdir.'/'.$path, $out, $status3);
     }
      //$rendered_message = \Drupal\Core\Render\Markup::create(implode(" " ,$message));
     //\Drupal::logger('dataset_validation_output')->debug(t('@out', ['@out' => $rendered_message]));
     //print the output of the compliance checker as taken from the html output.
     if($status !==0){
        \Drupal::messenger()->addMessage(t("Your dataset is not compliant with the required test(s)."), 'warning');
        $message = str_replace($fdir, "", $out);
        $redirect = 'dataset_validation.outcome';
        //\Drupal::messenger()->addMessage($message, 'warning');
     }else{
        \Drupal::messenger()->addMessage(t("Congratulations! Your dataset is compliant with the required test."), 'status');
        $message = '<div class="w3-conatainer w3-leftbar w3-border-green w3-margin w3-panel w3-pale-green"><span>Congratulations! Your dataset is compliant with the required test</span></div>';
        $redirect = 'dataset_validation.form';
        //\Drupal::messenger()->addMessage($message, 'status');
     }
     //Remove submission file and DB entry.
     //$fid = $fname["#file"]->fid;
  /*   $file_notsaved = file_load($fid);
     file_delete($file_notsaved);
     //Remove html files
     if(file_exists($ex_out_cf)){
        file_unmanaged_delete($ex_out_cf);
     }
     if(file_exists($ex_out_acdd)){
        file_unmanaged_delete($ex_out_acdd);
     }
     */
     $file->delete();

     $session = \Drupal::request()->getSession();
      $session->set("result1", $message);
       //$this->redirect('dataset_validation.outcome');
       $form_state->setRedirect($redirect);
       return;
     }
   }
