<div class="crm-section">
  <h3>{ts domain='Synopsis'}Configuration upload page{/ts}</h3>
  {ts domain='Synopsis'}Notice: If you upload a configuration file (JSON format) your previous configuration will be overriden and cannot be reverted back!{/ts}<br/>
  {ts domain='Synopsis'}Please be careful!{/ts}<br/>
</div>
<hr>
<div class="crm-section">
  <div class="label">{$form.config_files.label}</div>
  <div class="content">{$form.config_files.html}</div>
  <div class="clear"></div>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
<div id="help" class="description" style="margin-top:30px;">
  <a href="{crmURL p="civicrm/admin/settings/synopsis"}">{ts domain='Synopsis'}Go <strong>back</strong> to the management interface.{/ts}</a>
</div>
{if $is_import}
  {literal}
    <script type="application/javascript">
      cj(document).ready(function() {
          cj("#config_files").closest("form").attr('enctype' ,'multipart/form-data');
          cj("#config_files").attr('name', 'config_files[]');
      });
    </script>
  {/literal}
{/if}
