<div class="updatebtn" style="padding:5px 0;margin-bottom:25px;">
<a id="updatecontact" class="no-popup button" href="#">
  <span>
    <i class="crm-i fa-refresh"></i>&nbsp;{ts domain='Synopsis'}Update this contact information{/ts}
  </span>
</a>
</div>
{literal}
<script>
  CRM.$(document).ready(function(){
    CRM.$("#updatecontact").on("click", function(){
      CRM.api3('Synopsis', 'calculate', {"contact_id": {/literal}{$contactId}{literal}}).done(function(result){
        location.reload(true);
        CRM.alert('Refresh successful', 'Synopsis contact update', 'success', {expires: 10000});
      });
    }); //end on click button
  });
</script>
{/literal}
