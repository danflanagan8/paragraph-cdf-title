<?php

/**
 * Implements hook_token_info()
 * Custom Paragraph CDF tokens
 */
function paragraph_cdf_title_token_info(){
  $info = array();
  $info['tokens']['cdf'] = array(
   'bundle-machine' => array(
     'name' => t('Bundle (Machine)'),
     'description' => 'Paragraph Bundle (Machine Name)',
     'needs-data' => array('cdf'),
   ),
   'bundle-label' => array(
     'name' => t('Bundle (Label)'),
     'description' => 'Paragraph Bundle (Human Readable Name)',
     'needs-data' => array('cdf'),
   ),
   'uuid' => array(
     'name' => t('UUID'),
     'description' => 'Paragraph UUID',
     'needs-data' => array('cdf'),
   ),
  );
  $info['types']['cdf'] = array(
    'name' => t('Paragraph CDF'),
    'description' => t('Paragraph CDF tokens'),
  );
  return $info;
}

/**
 * Implements hook_tokens()
 * Custom Paragraph CDF tokens
 */
function paragraph_cdf_title_tokens($type, $tokens, array $data = array(), array $options = array()){
  $replacements = array();
  if ($type == 'cdf' && !empty($data['cdf'])) {
    $cdf = $data['cdf'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'bundle-machine':
          $paragraph_type = $cdf->getAttribute('type')['value']['en'];
          $replacements[$original] = $paragraph_type;
          break;
        case 'bundle-label':
          $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('paragraph');
          $paragraph_type = $cdf->getAttribute('type')['value']['en'];
          $label = $bundles[$paragraph_type]['label'];
          $replacements[$original] = $label;
          break;
        case 'uuid':
          $replacements[$original] = $cdf->getUuid();
          break;
      }
    }
  }
  // An array of replacement values keyed by original token string.
  return $replacements;
}
