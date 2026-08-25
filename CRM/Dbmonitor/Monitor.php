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
 * Tools to monitor DB load, stuck queries, etc.
 */
class CRM_Dbmonitor_Monitor {

  protected static bool $monitoring_temporarily_disabled = FALSE;

  /**
   * Get a list of stuck queries.
   *  Fields: id, runtime, state, sql
   *
   * @return list<array{
   *   id: int|string,
   *   runtime: int|string,
   *   runtime_text: string,
   *   state: string,
   *   sql: string|null,
   *   type: string,
   *   sql_short: string,
   *   db: string|null,
   *   }>
   */
  public static function getStuckQueries(): array {
    static $stuck_queries = NULL;
    if ($stuck_queries === NULL) {
      $stuck_queries = [];

      // get some params
      $database  = CRM_Core_DAO::singleValueQuery('SELECT DATABASE()');
      $threshold = self::getThreshold();

      /** @var CRM_Core_DAO $process_list */
      $process_list = CRM_Core_DAO::executeQuery('SHOW FULL PROCESSLIST;');
      while ($process_list->fetch()) {
        if ($process_list->Time >= $threshold && $process_list->State !== NULL && $process_list->State !== '') {
          $sql = is_string($process_list->Info) ? $process_list->Info : NULL;
          $db = is_string($process_list->db) ? $process_list->db : NULL;
          $stuck_queries[] = [
            'id'           => $process_list->Id,
            'runtime'      => $process_list->Time,
            'runtime_text' => self::renderRuntime($process_list->Time),
            'state'        => $process_list->State,
            'sql'          => $sql,
            'type'         => self::getQueryType($sql ?? ''),
            'sql_short'    => substr($sql ?? '', 0, 64),
            'db'           => ($db === $database) ? '' : $db,
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
  public static function injectWarning(): void {
    if (self::monitoringEnabledForUser()) {
      $queries = self::getStuckQueries();
      if (count($queries) > 0) {
        $url = CRM_Utils_System::url('civicrm/admin/dbprocesslist');
        if (count($queries) > 1) {
          $threshold = self::renderRuntime(self::getThreshold());
          CRM_Core_Session::setStatus(
              E::ts(
                  'There are currently %1 queries in the database that have been running for more than %2. '
                  . 'You should check that <a href="%3">HERE</a>.',
                  [1 => count($queries), 2 => $threshold, 3 => $url]
              ),
              E::ts('Warning: Conspicuous database queries detected!'),
              'warn'
          );
        }
        else {
          $runtime = self::renderRuntime($queries[0]['runtime']);
          CRM_Core_Session::setStatus(
              E::ts('A database query has been running for more than %1. You should check that <a href="%2">HERE</a>.',
                  [1 => $runtime, 2 => $url]),
              E::ts('Warning: Conspicuous database query detected!'),
              'warn'
                  );
        }
      }
    }
  }

  /**
   * Render a human-readable representation of the time in seconds
   * @param int|string $seconds
   * @return string time expression
   */
  public static function renderRuntime($seconds): string {
    $seconds = (int) $seconds;
    $hours   = intdiv($seconds, 3600);
    $minutes = intdiv($seconds, 60) % 60;
    $seconds = $seconds % 60;
    if ($hours > 0) {
      if ($minutes > 0) {
        return E::ts('%1 hours and %2 minutes', [1 => $hours, 2 => $minutes]);
      }
      else {
        return E::ts('%1 hours', [1 => $hours]);
      }
    }
    elseif ($minutes > 0) {
      if ($seconds > 0) {
        return E::ts('%1 minutes and %2 seconds', [1 => $minutes, 2 => $seconds]);
      }
      else {
        return E::ts('%1 minutes', [1 => $minutes]);
      }
    }
    else {
      return E::ts('%1 seconds', [1 => $seconds]);
    }
  }

  /**
   * Check if the query monitoring is enabled for the current user
   */
  public static function monitoringEnabledForUser(): bool {
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
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the list of permissions necessary to access the DB Monitor
   * @return list<string> list of permissions (or)
   */
  public static function getPermissions(): array {
    $permissions = Civi::settings()->get('dbmonitor_permissions');
    if (is_array($permissions)) {
      return array_values(array_filter($permissions, 'is_string'));
    }
    else {
      return ['administer CiviCRM'];
    }
  }

  /**
   * Is the in-page monitoring enabled?
   * @return bool enabled?
   */
  public static function monitoringEnabled() {
    if (self::$monitoring_temporarily_disabled) {
      return FALSE;
    }
    else {
      return (bool) Civi::settings()->get('dbmonitor_enabled');
    }
  }

  /**
   * temporarily disable monitoring
   */
  public static function disableMonitoring(): void {
    self::$monitoring_temporarily_disabled = TRUE;
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
    $threshold_raw = Civi::settings()->get('dbmonitor_threshold');
    $threshold = is_numeric($threshold_raw) ? (int) $threshold_raw : 0;
    if ($threshold !== 0) {
      return $threshold;
    }
    else {
      return (int) get_cfg_var('max_execution_time');
    }
  }

  /**
   * Send an email report of the stuck queries to the given email addresses
   *
   * @param list<string> $recipients
   *  recipients of the report, list of email addresses
   * phpcs:ignore Generic.Files.LineLength.TooLong
   * @param list<array{id: int|string, runtime: int|string, runtime_text: string, state: string, sql: string|null, type: string, sql_short: string, db: string|null}>|null $queries
   *  query list as produced by CRM_Dbmonitor_Monitor::getStuckQueries(). If null, will be pulled there
   *
   * @throws CRM_Core_Exception
   *   In case anything's wrong.
   */
  public static function sendEmailReport($recipients, $queries = NULL): void {
    if ($queries === NULL) {
      $queries = CRM_Dbmonitor_Monitor::getStuckQueries();
    }

    if (count($queries) > 0) {
      if (count($recipients) === 0) {
        throw new CRM_Core_Exception('No recipients');
      }

      // compile email
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
      $domain = CRM_Core_BAO_Domain::getDomain();
      $email = [
        'subject' => E::ts("DB Monitoring: Conspicuous queries spotted on '%1 (%2)'", [
          1 => self::getSiteName(),
          2 => $domain->_database,
        ]),
        'from'    => CRM_Utils_Mail::formatRFC822Email($domainEmailName, $domainEmailAddress),
      ];

      // render content
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('queries', $queries);
      $smarty->assign('dbmonitorlink', CRM_Utils_System::url('civicrm/admin/dbprocesslist', '', TRUE));
      $smarty_template = E::path('templates/probe_email.tpl');
      $email['html'] = $smarty->fetch($smarty_template);

      // add queries as attachments
      foreach ($queries as $query) {
        // write queries out as files to attach to email
        // remark: using the same files every time, so we don't clog up /tmp
        $file_name = "process-{$query['id']}.sql";
        $tmp_file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dbmonitor_' . $file_name;
        file_put_contents($tmp_file_name, $query['sql'] ?? '');

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

  protected static function getSiteName(): string {
    $base_url = CRM_Core_Config::singleton()->userFrameworkBaseURL;
    $url_parts = is_string($base_url) ? parse_url($base_url) : FALSE;
    $url_host = is_array($url_parts) ? ($url_parts['host'] ?? '') : '';
    $url_path = is_array($url_parts) ? ($url_parts['path'] ?? '') : '';
    return trim($url_host . $url_path, '/ ');
  }

  /**
   * Run a heuristic to determine the type/classification of the query
   *
   * @param string $sql
   *   SQL query
   *
   * @return string
   *   human readable string to give an indication of what kind of query it is
   */
  public static function getQueryType($sql) {
    // simply look for a couple if tell-tale strings in the query...
    if (preg_match('/INTO civicrm_tmp_._gccache/i', $sql) === 1) {
      return E::ts('GroupCache Rebuild');
    }
    if (preg_match('/_dedupe_/', $sql) === 1) {
      return E::ts('Deduplication');
    }
    if (preg_match('/civireport/', $sql) === 1) {
      return E::ts('CiviCRM Report');
    }
    return E::ts('Unknown');
  }

}
