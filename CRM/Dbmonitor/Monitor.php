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
 * Tools to monitor DB load, stuck queries, etc.
 */
class CRM_Dbmonitor_Monitor {

  protected static $monitoring_temporarily_disabled = false;

  /**
   * Get a list of stuck queries.
   *  Fields: id, runtime, state, sql
   *
   * @return array of arrays
   */
  public static function getStuckQueries() {
    static $stuck_queries = null;
    if ($stuck_queries === null) {
      $stuck_queries = [];

      // get some params
      $database  = DB::parseDSN(CIVICRM_DSN)['database'];
      $threshold = self::getThreshold();

      $process_list = CRM_Core_DAO::executeQuery("SHOW FULL PROCESSLIST;");
      while ($process_list->fetch()) {
        if ($process_list->Time >= $threshold && !empty($process_list->State)) {
          $stuck_queries[] = [
              'id'           => $process_list->Id,
              'runtime'      => $process_list->Time,
              'runtime_text' => self::renderRuntime($process_list->Time),
              'state'        => $process_list->State,
              'sql'          => $process_list->Info,
              'sql_short'    => substr($process_list->Info, 0, 64),
              'db'           => ($process_list->db == $database) ? '' : $process_list->db,
          ];
        }
      }
    }
    return $stuck_queries;
  }

  /**
   * Inject a status warning if there is
   *  a stuck query, but only if the current
   *  user has the function enabled
   */
  public static function injectWarning() {
    if (self::monitoringEnabledForUser()) {
      $queries = self::getStuckQueries();
      if (count($queries) > 0) {
        $url = CRM_Utils_System::url('civicrm/admin/dbprocesslist');
        if (count($queries) > 1) {
          $threshold = self::renderRuntime(self::getThreshold());
          CRM_Core_Session::setStatus(
              E::ts('There are currently %1 queries in the database that have been running for more than %2. You should check that <a href="%3">HERE</a>.',
                  [1 => count($queries), 2 => $threshold, 3 => $url]),
              E::ts("Warning: Conspicuous database queries detected!"),
              'warn'
          );
        } else {
          $runtime = self::renderRuntime($queries[0]['runtime']);
          CRM_Core_Session::setStatus(
              E::ts('A database query has been running for more than %1. You should check that <a href="%2">HERE</a>.',
                  [1 => $runtime, 2 => $url]),
              E::ts("Warning: Conspicuous database query detected!"),
              'warn'
          );
        }
      }
    }
  }

  /**
   * Render a human-readable representation of the time in seconds
   * @param $seconds
   * @return string time expression
   */
  public static function renderRuntime($seconds) {
    $hours   = floor($seconds / 3600);
    $minutes = floor($seconds / 60 % 60);
    $seconds = floor($seconds % 60);
    if ($hours) {
      if ($minutes) {
        return E::ts("%1 hours and %2 minutes", [1 => $hours, 2 => $minutes]);
      } else {
        return E::ts("%1 hours", [1 => $hours]);
      }
    } elseif ($minutes) {
      if ($seconds) {
        return E::ts("%1 minutes and %2 seconds", [1 => $minutes, 2 => $seconds]);
      } else {
        return E::ts("%1 minutes", [1 => $minutes]);
      }
    } else {
      return E::ts("%1 seconds", [1 => $seconds]);
    }
  }

  /**
   * Check if the query monitoring is enabled for the current user
   */
  public static function monitoringEnabledForUser() {
    return self::monitoringEnabled() && self::userHasMonitoringPermissions();
  }

  /**
   * Check if the current user has monitoring permissions
   *
   * @return bool
   */
  public static function userHasMonitoringPermissions() {
    $permissions = self::getPermissions();
    foreach ($permissions as $permission) {
      if (CRM_Core_Permission::check($permission)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Get the list of permissions necessary to access the DB Monitor
   * @return array list of permissions (or)
   */
  public static function getPermissions() {
    $permissions = Civi::settings()->get('dbmonitor_permissions');
    if (is_array($permissions)) {
      return $permissions;
    } else {
      return ['administer CiviCRM'];
    }
  }

  /**
   * Is the in-page monitoring enabled?
   * @return bool enabled?
   */
  public static function monitoringEnabled() {
    if (self::$monitoring_temporarily_disabled) {
      return false;
    } else {
      return (bool) Civi::settings()->get('dbmonitor_enabled');
    }
  }

  /**
   * temporarily disable monitoring
   */
  public static function disableMonitoring() {
    self::$monitoring_temporarily_disabled = true;
  }

  /**
   * Is the injected per-call monitoring enabled?
   *
   * @return boolean enabled?
   */
  public static function warningsEnabled() {
    return (bool) Civi::settings()->get('dbmonitor_warnings');
  }

  /**
   * Get the runtime threshold with which e query
   *  is considered "stuck"
   *
   * @return integer time in seconds
   */
  public static function getThreshold() {
    $threshold = (int) Civi::settings()->get('dbmonitor_threshold');
    if ($threshold) {
      return $threshold;
    } else {
      return (int) get_cfg_var('max_execution_time');
    }
  }

    /**
     * Send an email report of the stuck queries to the given email addresses
     *
     * @param array $recipients
     *  recipients of the report, list of email addresses
     * @param array $queries
     *  query list as produced by CRM_Dbmonitor_Monitor::getStuckQueries(). If null, will be pulled there
     *
     * @throws Exception
     *   in case anything's wrong
     */
  public static function sendEmailReport($recipients, $queries = null)
  {
      if ($queries === null) {
          $queries = CRM_Dbmonitor_Monitor::getStuckQueries();
      }

      if (!empty($queries)) {
          if (empty($recipients)) {
              throw new Exception("No recipients");
          }

          // compile email
          $url_parts = parse_url(CRM_Core_Config::singleton()->userFrameworkBaseURL);
          list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
          $domain = CRM_Core_BAO_Domain::getDomain();
          $email = [
              'subject' => E::ts("DB Monitoring: Stuck queries on '%1 (%2)'", [
                  1 => trim($url_parts['host'] . $url_parts['path'], '/ '),
                  2 => $domain->_database
              ]),
              'from'    => CRM_Utils_Mail::formatRFC822Email($domainEmailName, $domainEmailAddress),
          ];

          // render content
          $smarty = CRM_Core_Smarty::singleton();
          $smarty->assign('queries', $queries);
          $smarty_template = E::path('templates/probe_email.tpl');
          $email['html'] = $smarty->fetch($smarty_template);

          // add queries as attachments
          foreach ($queries as $query) {
              // write queries out as files to attach to email
              // remark: using the same files every time, so we don't clog up /tmp
              $file_name = "process-{$query['id']}.sql";
              $tmp_file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dbmonitor_' .  $file_name;
              file_put_contents($tmp_file_name, $query['sql']);

              // and add as attachment
              $email['attachments'][] = [
                  'fullPath'  => $tmp_file_name,
                  'mime_type' => 'application/sql',
                  'cleanName' => $file_name,
              ];
          }

          // finally: send out to each contact individually
          foreach ($recipients as $recipient) {
              $email['toEmail'] = $recipient;
              CRM_Utils_Mail::send($email);
          }
      }
  }
}
