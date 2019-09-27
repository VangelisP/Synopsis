{* HEADER *}
<div class="help">
  {ts domain='synopsis'}Please select below what would you like to include to your queries. They will be available as tokens.{/ts}
</div>
{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    {if $elementName|array_key_exists:$synopsis_fieldlegend}
      <div class="help">{$synopsis_fieldlegend[$elementName]}</div>
    {/if}
    <div class="spacer"></div>
    <div class="clear"></div>
  </div>
{/foreach}
<div class="help">
  Go back to <a href="{crmURL p="civicrm/admin/settings/synopsis"}">{ts domain='synopsis'}Synopsis field configuration{/ts}</a>.<br />
</div>
{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
