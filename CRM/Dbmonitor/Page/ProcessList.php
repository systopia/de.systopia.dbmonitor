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

class CRM_Dbmonitor_Page_ProcessList extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Conspicuous Database Queries'));

    if (!CRM_Dbmonitor_Monitor::userHasMonitoringPermissions()) {
      throw new Exception(E::ts("You don't have the permission required to view this page."));
    }

    // disable the warning for this page
    CRM_Dbmonitor_Monitor::disableMonitoring();

    // process ops
    $operation = CRM_Utils_Request::retrieve('op', 'String');
    $query_id  = CRM_Utils_Request::retrieve('id', 'Integer');
    $this->performOperation($operation, $query_id);

    // just add the queries
    $this->assign('queries', CRM_Dbmonitor_Monitor::getStuckQueries());

    // that's it
    parent::run();
  }

  /**
   * Execute the passed operation
   *
   * @param $operation string operation name
   * @param $query_id  int    query id
   */
  protected function performOperation($operation, $query_id) {
    switch ($operation) {
      case 'kill':
        // kill the query
        if ($query_id) {
          CRM_Core_DAO::executeQuery("KILL QUERY {$query_id};");
        }
        CRM_Core_Session::setStatus(
            E::ts("Terminated query [%1].", [1 => $query_id]),
            E::ts("Query terminated"),
            'info');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/dbprocesslist'));

      case 'export':
        // export the query SQL
        $queries = CRM_Dbmonitor_Monitor::getStuckQueries();
        foreach ($queries as $query) {
          if ($query['id'] == $query_id) {
            CRM_Utils_System::download(
                E::ts("conspicuous_query_%1", [1 => $query_id]),
                'application/sql',
                $query['sql'],
                'sql',
                TRUE
            );
          }
        }
        CRM_Core_Session::setStatus(
            E::ts("Query [%1] couldn't be found any more.", [1 => $query_id]),
            E::ts("Query not found"),
            'warn');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/dbprocesslist'));
    }
  }
}
