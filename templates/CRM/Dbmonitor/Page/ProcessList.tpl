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
<div class="crm-block crm-content-block crm-twingle-content-block">
{if !empty($queries)}
    <table>
        <thead>
        <tr>
            <th>{ts}Process ID{/ts}</th>
            <th>{ts}Status{/ts}</th>
            <th>{ts}Query{/ts}</th>
            <th>{ts}Running Since{/ts}</th>
            <th>{ts}Actions{/ts}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$queries item=query}
            {assign var="query_id" value=$query.id}
            <tr>
                <td>{$query.id}</td>
                <td>{$query.state}</td>
                <td><a title="{$query.sql}">{$query.sql_short}</a></td>
                <td>{$query.runtime_text}</td>
                <td>
                    <a href="{crmURL p="civicrm/admin/dbprocesslist" q="op=kill&id=$query_id"}" title="{ts}Cancel the query{/ts}" class="action-item crm-hover-button">{ts}Kill{/ts}</a>
                    <a href="{crmURL p="civicrm/admin/dbprocesslist" q="op=export&id=$query_id"}" title="{ts}Export SQL{/ts}" class="action-item crm-hover-button">{ts}Export{/ts}</a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
{else}
    <h3>{ts}There are currently no conspicuous queries running in the database{/ts}</h3>
{/if}
</div>
{/crmScope}