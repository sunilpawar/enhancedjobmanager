<?php

require_once 'enhancedjobmanager.civix.php';

use CRM_Enhancedjobmanager_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function enhancedjobmanager_civicrm_config(&$config): void {
  _enhancedjobmanager_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function enhancedjobmanager_civicrm_install(): void {
  _enhancedjobmanager_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function enhancedjobmanager_civicrm_enable(): void {
  _enhancedjobmanager_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_alterMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterMenu
 */
function enhancedjobmanager_civicrm_alterMenu(&$items) {
  $items['civicrm/admin/job']['page_callback'] = 'CRM_Enhancedjobmanager_Page_Job';
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function enhancedjobmanager_civicrm_navigationMenu(&$menu) {
  _enhancedjobmanager_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => E::ts('Job Statistics'),
    'name' => 'job_statistics',
    'url' => 'civicrm/admin/job/stats',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-chart-bar',
  ]);

  // Also add to the main job management area
  _enhancedjobmanager_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => E::ts('Enhanced Job Manager'),
    'name' => 'enhanced_job_manager',
    'url' => 'civicrm/admin/job/enhanced',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-tasks',
  ]);

  _enhancedjobmanager_civix_navigationMenu($menu);
}
