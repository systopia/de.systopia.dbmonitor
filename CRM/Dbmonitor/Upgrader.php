<?php
declare(strict_types = 1);
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
 * Collection of upgrade steps.
 */
class CRM_Dbmonitor_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Install process
   */
  public function install(): void {
    $this->addScheduledJob();
  }

  /**
   * Add scheduled job with 0.3
   */
  public function upgrade_0031(): bool {
    $this->ctx->log->info('Adding scheduled job');
    $this->addScheduledJob();
    return TRUE;
  }

  /**
   * Helper function to install a scheduled monitoring job
   */
  protected function addScheduledJob(): void {
    $count = (int) civicrm_api3(
        'Job',
        'getcount',
        [
          'api_entity' => 'DBMonitor',
          'api_action' => 'probe',
        ]
    );

    if ($count === 0) {
      // job doesn't exist => create
      civicrm_api3(
        'Job',
        'create',
        [
          'name'          => E::ts('Check for conspicuous database queries'),
          'description'   => E::ts(
                'Will check periodically if anything suspicious is going on in the database, and send email warnings.'
          ),
          'parameters'    => E::ts('email_recipients=you@yourdomain.todo'),
          'api_entity'    => 'DBMonitor',
          'api_action'    => 'probe',
          'run_frequency' => 'Always',
          'is_active'     => 0,
        ]
      );
    }
  }

}
