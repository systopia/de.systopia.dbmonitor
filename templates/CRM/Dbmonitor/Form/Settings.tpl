{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.dbmonitor'}
<div class="crm-block crm-form-block crm-dbmonitor-form-block">
  <br/>
  <div class="crm-section">
    <div class="label">{$form.permissions.label}</div>
    <div class="content">{$form.permissions.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.threshold.label}</div>
    <div class="content">{$form.threshold.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.monitoring.label}</div>
    <div class="content">{$form.monitoring.html}</div>
    <div class="clear"></div>
  </div>
  <br/>
</div>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{/crmScope}