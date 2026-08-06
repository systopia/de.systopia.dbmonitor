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
 * Adjust Metadata for DBMonitor.probe
 *
 * @param array<string, mixed> $params
 */
function _civicrm_api3_d_b_monitor_probe_spec(array &$params): void {
  $params['email_recipients'] = [
    'name'         => 'email_recipients',
    'api.required' => 0,
    'title'        => 'Email recipients',
    'description'  => 'Comma-separated list of recipients. If empty uses current user',
  ];
}

/**
 * API Action DBMonitor.probe
 *
 * Check the system for stuck queries and send an email if there are any
 *
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
function civicrm_api3_d_b_monitor_probe(array &$params): array {
  $queries = CRM_Dbmonitor_Monitor::getStuckQueries();
  if (count($queries) === 0) {
    // no stuck queries detected
    return civicrm_api3_create_success(E::ts('No stuck queries detected'));
  }

  // find out recipient emails
  $recipients_emails = [];
  if (!isset($params['email_recipients']) || $params['email_recipients'] === '') {
    $contact_id = CRM_Core_Session::getLoggedInContactID();
    if ($contact_id !== NULL && $contact_id > 0) {
      try {
        $recipients_emails[] = civicrm_api3(
        'Email',
        'getvalue',
        [
          'return'       => 'email',
          'contact_id'   => $contact_id,
          'is_primary'   => 1,
          'option.limit' => 1,
        ]
        );
      }
      catch (CRM_Core_Exception $ex) {
        // @ignoreException contact doesn't seem to have a primary email
      }
    }

  }
  else {
    foreach (preg_split('/,/', $params['email_recipients']) as $email) {
      $email = trim($email);
      if ($email !== '') {
        $recipients_emails[] = $email;
      }
    }
  }

  if (count($recipients_emails) === 0) {
    if (!isset($params['email_recipients']) || $params['email_recipients'] === '') {
      return civicrm_api3_create_error(E::ts('Current user has no valid email.'));
    }
    else {
      return civicrm_api3_create_error(E::ts('No valid email addresses provided.'));
    }
  }

  // all good: render and send
  CRM_Dbmonitor_Monitor::sendEmailReport($recipients_emails, $queries);

  return civicrm_api3_create_success(E::ts('%1 stuck queries sent to %2 recipient(s)', [
    1 => count($queries),
    2 => count($recipients_emails),
  ]));
}
