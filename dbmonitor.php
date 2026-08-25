<?php
// phpcs:disable PSR1.Files.SideEffects
declare(strict_types = 1);

require_once 'dbmonitor.civix.php';
use CRM_Dbmonitor_ExtensionUtil as E;

/**
 * Implements hook_civicrm_pageRun().
 */
function dbmonitor_civicrm_pageRun(object &$page): void {
  CRM_Dbmonitor_Monitor::injectWarning();
}

/**
 * Implements hook_civicrm_buildForm().
 */
function dbmonitor_civicrm_buildForm(string $formName, CRM_Core_Form &$form): void {
  CRM_Dbmonitor_Monitor::injectWarning();
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function dbmonitor_civicrm_config(CRM_Core_Config &$config): void {
  _dbmonitor_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function dbmonitor_civicrm_install(): void {
  _dbmonitor_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function dbmonitor_civicrm_enable(): void {
  _dbmonitor_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
 *
 * // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
 * function dbmonitor_civicrm_navigationMenu(&$menu) {
 * _dbmonitor_civix_insert_navigation_menu($menu, 'Mailings', array(
 * 'label' => E::ts('New subliminal message'),
 * 'name' => 'mailing_subliminal_message',
 * 'url' => 'civicrm/mailing/subliminal',
 * 'permission' => 'access CiviMail',
 * 'operator' => 'OR',
 * 'separator' => 0,
 * ));
 * _dbmonitor_civix_navigationMenu($menu);
} // */
