# de.systopia.dbmonitor

+ checks the system for stuck database queries and sends an email alert if there are any.
+ The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.4+
* CiviCRM (*FIXME: Version number*)

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl de.systopia.dbmonitor@https://github.com/FIXME/de.systopia.dbmonitor/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/systopia/de.systopia.dbmonitor.git
cv en dbmonitor
```

## Usage

This extensions will give admins a notification bubble if any of CiviCRM's database queries is taking a very long time, 
and gives you the options to stop (i.e. "kill") it. Use this with caution.

### Settings
are available at:
+ _your_domain_/civicrm/admin/setting/dbmonitor
+ admin_console -> system-settings -> DB Monitor Settings

here you have to adjust:
+ permission (to be added as an email-recipient)
+ threshold in seconds (the duration after which a database-query is supposed to be stuck)

here you may
+ enable/disable Monitoring at all

### APIv3
this extension is adding the entity **DBmonitor** to APIv3.
yet there is only one action implemented: **probe**

#### ``DBmonitor.probe``
check the system for stuck queries and send an email if there are any.
+ as a parameter you may add a comma-separated list of email-recipients. 
if empty, the current user is addressed.
+ the email's 'from address' is the 'from Email Address Option' with the value: 1
+ the email's subject is 'DB Monitoring: Conspicuous queries spotted on _host_ _path_ _database_'

## continuous monitoring via cron-job
This extension adds a new cron-job: **Check for conspicuous database queries**.
+ the cron-job is executing the APIv3-action **``DBmonitor.probe``**
+ as a parameter you may add: **email_recipients=** followed by a comma-separated list of email-addresses
