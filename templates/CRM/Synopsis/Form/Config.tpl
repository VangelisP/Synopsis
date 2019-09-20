{* HEADER *}
<div class="help">
  {ts domain='Synopsis'}Please select below which kind of optionsvalues you would like to include to the queries. They will be available as tokens.
  For example, for financial types, the proper token to use in the query is `&lbrace;financial_types&rbrace;`.{/ts}
</div>
{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}
<div class="help">
  Go back to <a href="{crmURL p="civicrm/admin/settings/synopsis"}">{ts domain='Synopsis'}Synopsis field configuration{/ts}</a>.<br />
</div>
{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
