Create main customgroup during installation
Store customgroup API call in settings
uninstall by deleting all customfields of customgroup AND customgroup itself

## DB/memory

Ability to read the stored configuration inside `CRM_Synopsis_Config->get_params()`;
Ability to store/append the stored configuration inside `CRM_Synopsis_Config->set_params($params)`;

## Configuration

* [x] Function will always write either:
  * integer
  * date
  * text

* [x] Each defined field will have:
  * CFID (empty on new entry)
  * Title (text)
  * Description (text)
  * DataType (selector)
  * Select Reference (if selector is being selected above)
  * Query (Textarea)
  * Option Group (selector)
  * Graph (boolean checkbox) / Will be removed probably
  * Remove (boolean checkbox)

* [x] Add JSON Library editor on config page
* [x] On config page, ability to add on demand new fields.
* [ ] If fields are selector fields, leave the optionvalues empty but be able to associate with existing select fields. Skip creating empty optiongroup
* [x] On form save, save configuration, create (recreate) those customfields
* [x] Sort order works only through customfield UI and after changing order, you need to save the form for changes to reflect
* [ ] Add proper weight
* [x] Remove 'Edit Synopsis Fields' button
* [x] Remove 'Delete' button
* [x] Add 'Refresh contact's Synopsis' button
* [ ] Add Graphs
* [x] Add labels for arrays
* [x] Check if same columns exist in both temp and normal table
* [x] Store initial table name somewhere in the settings
* [x] Add more tokens
* [ ] Add charts (Charts.js)
* [x] Configuration importer



## Engine

`function synopsis_civicrm_fieldOptions`
* [x] Use this function to intercept possibly select fields and do the proper associations here. This way, all integer/numeric stored values will be converted into label on search/reports globally
* [x] Calculation process into a temporary table
* [x] Storing/updating the tmp table into the proper table
* [x] calculation/storing/updating using a specific contact ID

## Logic

* [x] Create entries with cfid empty
* [x] On save, if cfid is empty, api create fields and get the cfid.
* [x] If cfid is filled, use that cfid to update via API
* [x] Store the cfid on each entry
* [x] On next load, load the cfid along with the other data
* [x] On deletion, get the cfid, API delete the customfield, unset the array for that record and store the value


## Settings

synopsis_config
  - field_config
  - field_selectors
  - global_configuration
  - table_configuration -> field_table_id / field_table_name
