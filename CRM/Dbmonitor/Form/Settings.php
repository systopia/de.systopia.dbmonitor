<?php
/*-------------------------------------------------------+
| DB Monitoring                                          |
| Copyright (C) 2020 SYSTOPIA                            |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Dbmonitor_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Dbmonitor_Form_Settings extends CRM_Core_Form {
  public function buildQuickForm() {

    if (!CRM_Dbmonitor_Monitor::userHasMonitoringPermissions()) {
      throw new Exception(E::ts("You don't have the permission required to edit the DB monitoring settings."));
    }

    $this->add(
        'checkbox',
        'monitoring',
        E::ts('Monitoring Enabled?')
    );

    $this->add(
        'select',
        'permissions',
        E::ts('Permissions'),
        CRM_Core_Permission::basicPermissions(true),
        TRUE,
        ['class' => 'crm-select2', 'multiple' => 'multiple']
    );

    $this->add(
        'text',
        'threshold',
        E::ts('Threshold (seconds)'),
        [],
        TRUE
    );

    $this->addButtons([
        [
            'type'      => 'submit',
            'name'      => E::ts('Save'),
            'isDefault' => TRUE,
        ]
    ]);

    // set defaults
    $this->setDefaults([
        'monitoring'  => CRM_Dbmonitor_Monitor::monitoringEnabled(),
        'permissions' => CRM_Dbmonitor_Monitor::getPermissions(),
        'threshold'   => CRM_Dbmonitor_Monitor::getThreshold(),
    ]);

    parent::buildQuickForm();
  }

  public function postProcess() {
    if (!CRM_Dbmonitor_Monitor::userHasMonitoringPermissions()) {
      throw new Exception(E::ts("You don't have the permission required to edit the DB monitoring settings."));
    }

    $values = $this->exportValues();

    // set values
    Civi::settings()->set('dbmonitor_enabled', CRM_Utils_Array::value('monitoring', $values, 0));
    Civi::settings()->set('dbmonitor_threshold', $values['threshold']);
    Civi::settings()->set('dbmonitor_permissions', $values['permissions']);

    parent::postProcess();
  }
}
