<?php

namespace Drupal\paragraph_cdf_title\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Paragraph Titles for CDF export.
 */
class ParagraphCDFTitleForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraph_cdf_title_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'paragraph_cdf_title.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('paragraph_cdf_title.settings');
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('paragraph');

    $form['default'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => 'Default Settings',
      '#description' => 'Available custom tokens: [cdf:uuid], [cdf:bundle:machine], [cdf:bundle:label]. Paragraphs currently have poor out-of-the-box token support.'
    ];
    $field_options = $this->getFieldOptions(array_keys($bundles));
    $form['default']['primary'] = [
      '#type' => 'select',
      '#title' => t('Primary Title'),
      '#options' => ['' => '-Select-'] + $field_options,
      '#default_value' => isset($config->get('default')['primary']) ? $config->get('default')['primary'] : '',
      '#required' => FALSE,
    ];
    $form['default']['secondary'] = [
      '#type' => 'select',
      '#title' => t('Fallback Title'),
      '#options' => ['' => '-Select-'] + $field_options,
      '#default_value' => isset($config->get('default')['secondary']) ? $config->get('default')['secondary'] : '',
      '#required' => FALSE,
    ];
    $form['default']['constant'] = [
      '#type' => 'textfield',
      '#title' => t('Desperation Title'),
      '#default_value' => isset($config->get('default')['constant']) ? $config->get('default')['constant'] : '',
      '#required' => TRUE,
      '#description' => 'Title of last resort. Used if all selected fields are empty.',
    ];
    $form['default']['prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Title Prefix (always used if configured)'),
      '#default_value' => isset($config->get('default')['prefix']) ? $config->get('default')['prefix'] : '',
      '#required' => FALSE,
      '#description' => 'Text that should <b>always</b> be prepended to the newly created CDF Paragraph title even if the Paragraph is using bundle-specific settings.',
    ];
    $form['default']['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Title Suffix (always used if configured)'),
      '#default_value' => isset($config->get('default')['suffix']) ? $config->get('default')['suffix'] : '',
      '#required' => FALSE,
      '#description' => 'Text that should <b>always</b> be appended to the newly created CDF Paragraph title even if the Paragraph is using bundle-specific settings.',
    ];
    $form['default']['tokens'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => array('node'),
      '#description' => 'Any node token will be understood as the closest ancestor node to which this Paragraph is attached. For example, the Paragraph might be attached to a Paragraph that is attached to a node; that\'s the node the tokens would use.'
    );

    $form['paragraphs'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => 'Bundle-Specific Settings',
      '#prefix' => '<div id="paragraphs-wrapper">',
      '#suffix' => '</div>',
    ];
    foreach($bundles as $key=>$val){
      $form['paragraphs'][$key] = [
        '#type' => 'fieldset',
        '#title' => $val['label'],
        '#description' => $key,
      ];
      $field_options = $this->getFieldOptions($key);
      $form['paragraphs'][$key][] = [
        '#type' => 'select',
        '#title' => t('Primary Title'),
        '#options' => ['' => '-Select-'] + $field_options,
        '#default_value' => isset($config->get('paragraphs')[$key][0]) ? $config->get('paragraphs')[$key][0] : '',
        '#required' => FALSE,
      ];
      $form['paragraphs'][$key][] = [
        '#type' => 'select',
        '#title' => t('Fallback Title'),
        '#options' => ['' => '-Select-'] + $field_options,
        '#default_value' => isset($config->get('paragraphs')[$key][1]) ? $config->get('paragraphs')[$key][1] : '',
        '#required' => FALSE,
        '#states' => array(
          'invisible' => array(
            ':input[name="paragraphs[' . $key .'][0]"]' => array('value' => ''),
          ),
        ),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('paragraph_cdf_title.settings');
    $default = $form_state->getValue(['default']);
    $config->set('default', $form_state->getValue(['default']));
    $paragraphs = $form_state->getValue(['paragraphs']);
    $paragraphs_clean = [];
    foreach($paragraphs as $bundle=>$fields){
      $clean_fields = array_filter($fields);
      if(!empty($clean_fields)){
        $paragraphs_clean[$bundle] = array_slice($clean_fields, 0);
      }
    }
    $config->set('paragraphs', $paragraphs_clean);
    $config->save();
    parent::submitForm($form, $form_state);
  }

  public function getFieldOptions($paragraph_type){
    if(is_array($paragraph_type)){
      //get all possible fields
      $fields = [];
      foreach($paragraph_type as $bundle){
        $fields = array_merge($fields, \Drupal::entityManager()->getFieldDefinitions('paragraph', $bundle));
      }
    }else{
      $fields = \Drupal::entityManager()->getFieldDefinitions('paragraph', $paragraph_type);
    }
    $field_options = [];
    $allowed_types = [
      'string',
      'uuid',
      'integer',
    ];
    $forbidden_fields = [
      'id',
      'revision_id',
      'parent_id',
      'parent_type',
      'parent_field_name',
    ];
    foreach($fields as $key=>$field){
      if(in_array($field->getType(), $allowed_types) && !in_array($key, $forbidden_fields)){
        $label = is_string($field->getLabel()) ? $field->getLabel() : $key;
        $field_options[$key] = $label;
      }
    }
    return $field_options;
  }

}
