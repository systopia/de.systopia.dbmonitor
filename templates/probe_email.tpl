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
{literal}
<style>
    tr { border: .1pt }
    table { border: solid }
    thead { background: white }
    tr.odd { background: grey }
    tr.even { background: lightgrey }
</style>
{/literal}
 <body>
    <h3>{ts}Conspicuous queries in CiviCRM's database{/ts}</h3>
    <table >
        <thead>
        <tr>
            <th>{ts}Process ID{/ts}</th>
            <th>{ts}Status{/ts}</th>
            <th>{ts}Type{/ts}</th>
            <th>{ts}Query{/ts}</th>
            <th>{ts}Running Since{/ts}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$queries item=query}
            {assign var="query_id" value=$query.id}
            <tr>
                <td>{$query.id}</td>
                <td>{$query.state}</td>
                <td>{$query.type}</td>
                <td><a title="{$query.sql}">{$query.sql_short}</a></td>
                <td>{$query.runtime_text}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>

    <p>{ts 1=$dbmonitorlink}You have the option to kill some of those processes <a href="%1">HERE</a>.{/ts}</p>
 </body>
{/crmScope}