# Development

## How does the MySQL query is getting constructed

The structure of the query is segmented in 3 different parts.

### Initial part (the header)

```sql
SELECT
  c.id as entity_id,
```

### Snippet part

We loop each field and render its associated MySQL snippet.
Each MySQL snippet should bring **only 1 column** strictly!

### Final part (the footer)

```sql
FROM civicrm_contact c
{where clause}
GROUP BY c.id
```

The `{where clause}` is getting used if we specified a `contact_id` parameter during the API call.

For example, lets take the example of count of contribution. if our field's MySQL snippet is this:

```sql
SELECT COALESCE(COUNT(t1.id), 0) FROM civicrm_contribution t1
JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
WHERE t1.contact_id = {contact_id} AND t1.contribution_status_id = 1 AND
t1.financial_type_id IN ({financial_types}) AND t1.is_test = 0
```
then the final MySQL query would become like this

```sql
SELECT
  c.id as entity_id,
  (SELECT COALESCE(COUNT(t1.id), 0) FROM civicrm_contribution t1
  JOIN civicrm_line_item t2 ON t1.id = t2.contribution_id
  WHERE t1.contact_id = {contact_id} AND t1.contribution_status_id = 1 AND
  t1.financial_type_id IN ({financial_types}) AND t1.is_test = 0),
FROM civicrm_contact c
{where clause}
GROUP BY c.id
```

Lastly, replacement of tokens take place where they will replace the placeholders `{contact_id}` and `{financial_types}` with their associated values.


## Debugging / Maintenance

Currently, you can debug your queries by using the parameter

### debug_only

By adding `debug_only=1` to your call, you'll get a printout of the complete MySQL query, <u>after</u> the token replacement but without actually storing the query results into the table.

### Extension maintenance

There are 2 **destructive** API call parameters at the moment that could prove useful in case you'having issues with corrupted configuration:

The main call: `drush cvapi Synopsis.Maintenance operation='parameter'`.

The 'parameter':

* `delete_stored_config` : Will delete the stored database configuration (but not the actual CustomFields)
* `delete_stored_customfields` : Will delete **all** customfields under CustomGroup called Synopsis