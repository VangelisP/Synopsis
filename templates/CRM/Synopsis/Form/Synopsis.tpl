{* HEADER *}
<h3>{ts domain='synopsis'}Guide{/ts}</h3>
<div class="crm-section">
  <div id="help" class="description">
    Configuring Synopsis fields requires db skills<br/><br/>
    <h4>Basic information</h4><br/>
    <strong>CustomField ID</strong> is the internal numeric ID that is being given when a CustomField is being created. Initially, this field is 0 but as soon as the customfield is being created, it will inherit the actual ID.<br/>
    <strong>Machine name</strong> is the internal name that is being given to a customfield. Initially, this field is empty but as soon as the customfield is being created, it will show up the machine name.<br/>
    <strong>Label</strong> is the label of your customfield.<br/>
    <strong>DataType</strong> is the type of customfield you want to create/use.<br/>
    <strong>Select Reference</strong> works only if you have picked up 'Selector' as Datatype above. This field will be ignored if datatype is not of type 'Selector'.<br/>
    <br/>
    <h4>Advanced information</h4><br/>
    <strong>Query</strong> is the actual query that needs to be run in order to bring results. Remember to use tokens as replacements for contact ID and/or financial types<br/>
    <strong>Option Group</strong> is a general categorization of that field.<br/>
    <br/>
    <h4>Misc</h4><br/>
    <strong>Graph</strong><br/>
    <strong>Remove</strong> If checked, this field will be permanently removed upon form saving.<br/>
    <br/>
    <h4>Operations</h4><br/>
    To <strong>add</strong> a new field, click on the '+ Entry' button.<br/>
    To <strong>delete</strong> a field, select the checkbox "Remove" and save the form..<br/>
    <br />
    <h4>Maintenance</h4><br/>
    You can import a fresh configuration (in JSON format) by clicking <a href="{crmURL p="civicrm/admin/settings/synopsis/configimport"}">{ts domain='Synopsis'}here{/ts}</a> but please keep in mind that it will <u>wipe out</u> completely the current configuration you already created.<br />
    <strong>There is NO undoing</strong> !<br /><br />
    You can manage replacement token values by clicking <a href="{crmURL p="civicrm/admin/settings/synopsis/config"}">{ts domain='Synopsis'}here{/ts}</a>.<br />
    <br />
  </div>
</div>

<div class="crm-section">
  <div class="crm-section">
    <div id="jsoneditor" style="width: 100%; min-height: 200px;"></div>
    <div class="clear"></div>
  </div>
  <div align="right">
    <font size="-2" color="gray">
    This brilliant <a href="https://github.com/json-editor/json-editor">JSON editor</a> is an enhancement to <a href="https://github.com/jdorn/json-editor">the original one</a> that was being developed by Jeremy Dorn.
    </font>
  </div>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

<script>
    // Variable declaration
    var optGroups = {$optGroups};
    var dataTypes = {$dataTypes};
    var availOptGroups = {$availOptGroups};
  {literal}

      cj(document).ready(function(){
        // Initialize the editor with a JSON schema
        // create the editor
        var container = document.getElementById('jsoneditor');
        var configuration = cj("input[name=configuration]").val();
        // Setup the options
        var options = {
          ajax: true,
          disable_collapse: false,
          disable_properties: true,
          disable_edit_json: false,
          disable_array_delete: true,
          prompt_before_delete: true,
          expand_height: true,
          theme: 'jqueryui',
          iconlib: 'jqueryui',
          schema: {
            type: "array",
            title: "Synopsis - Field configuration",
            uniqueItems: true,
            items: {
              type: "object",
              id: "arr_item",
              options: {
                collapsed: true
              },
              title: "Entry",
              headerTemplate: "ID: {{self.CFID}} - {{self.Label}} - [{{self.DataType}}]",
              properties: {
                Label: {
                  type: "string",
                  title: "Label",
                  options: {
                    inputAttributes: {
                      class: "synopsis field lbl"
                    }
                  }
                },
                MName: {
                  type: "string",
                  title: "Machine-name",
                  options: {
                    inputAttributes: {
                      class: "synopsis field mname readonly"
                    }
                  }
                },
                DataType: {
                  type: "string",
                  title: "DataType",
                  enum: dataTypes,
                  options: {
                    inputAttributes: {
                      class: "synopsis field datatype source"
                    }
                  }
                },
                SelectRef: {
                  type: "string",
                  title: "Select Reference",
                  enum: availOptGroups.names,
                  options: {
                    infoText: "Works only if you have picked up 'Selector' as Datatype above. If other than 'Selector', it will be ignored",
                    enum_titles: availOptGroups.titles,
                    inputAttributes: {
                      class: "synopsis field datatype selector"
                    }
                  }
                },
                Query: {
                  type: "string",
                  format: "textarea",
                  title: "Query",
                  options: {
                    infoText: "Always include the token {contact_id} so that the query is successful",
                    inputAttributes: {
                      class: "synopsis field squery"
                    }
                  }
                },
                Optgroup: {
                  type: "string",
                  title: "Option group",
                  enum: optGroups.names,
                  display_required_only: true,
                  options: {
                    enum_titles: optGroups.titles,
                    inputAttributes: {
                      class: "synopsis field optgroup"
                    }
                  }
                },
                Graph: {
                  type: "boolean",
                  title: "Graph",
                  format: "checkbox",
                  options: {
                    infoText: "Create a graphic while rendering this"
                  }
                },
                Remove: {
                  type: "boolean",
                  title: "Remove",
                  format: "checkbox",
                  options: {
                    infoText: "If checked, this entry/customfield will be PERMANENTLY removed during saving!",
                    inputAttributes: {
                      class: "synopsis checkbox remove"
                    }
                  }
                },
                CFID: {
                  type: "integer",
                  title: "CustomField ID",
                  options: {
                    inputAttributes: {
                      class: "synopsis field cfid readonly"
                    }
                  }
                },
                Weight: {
                  type: "string",
                  title: "Order",
                  format: "hidden",
                  options: {
                    compact: true
                  }
                },
                column_name: {
                  type: "string",
                  title: "DB Column name",
                  format: "hidden",
                  options: {
                    compact: true
                  }
                }
              }
            }
          },
          // Seed the form with a starting value
          startval: JSON.parse(configuration)
        };
        var editor = new JSONEditor(container, options, configuration);
        // Capture changes
        editor.on('change', function(){
          // Populate the configuration
          cj("input[name=configuration]").val(JSON.stringify(editor.getValue()));
          cj("#jsoneditor .synopsis.readonly").each(function(index, element){
            cj(this).prop('readOnly', true);
            cj(this).prop('tabIndex', -1);
          });
          cj("#jsoneditor .synopsis.field.datatype.source").each(function(index, element){
            if (cj(this).val() === 'Selector') {
              cj(this).parents().find('.synopsis.field.datatype.selector').prop('disabled', false);
            } else {
              cj(this).parents().find('.synopsis.field.datatype.selector').prop('disabled', true);
            }
          });
        });
      });
  </script>
{/literal}
