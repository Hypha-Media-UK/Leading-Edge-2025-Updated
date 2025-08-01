# Console Commands
Formie comes with a number of command line utilities that can be run on-demand, or on a set schedule.

## Forms

### Re-save Forms
Refer to the [Craft docs](https://craftcms.com/docs/4.x/console-commands.html#resave) on available options.

```shell
./craft resave/formie-forms --update-search-index=1
```

## Delete Forms
You can bulk delete forms with this command.

Option | Description
--- | ---
`--form-handle` | The form handle(s) to delete. Can be set to multiple comma-separated handles.
`--form-id` | The form ID(s) to delete. Can be set to multiple comma-separated IDs.

```shell
./craft formie/forms/delete --form-handle=form1,anotherForm
```

## Import/Export

### List Forms
Lists all available Formie forms that can be exported or imported.

Option | Description
--- | ---
`folderPath` | Optional path to look for JSON files. Defaults to the plugin's export folder.

```shell
./craft formie/forms/list
```

### Export Forms
Export Formie forms as JSON files. Requires form IDs or handles as a comma-separated list.

```shell
./craft formie/forms/export 1,contact-form,newsletter
```

### Import Form
Import a Formie form from a JSON file.

Option | Description
--- | ---
`fileLocation` | Path to a JSON file to import. Can be relative to the plugin's export folder or an absolute path.
`--create` | Whether to create a new form instead of updating an existing one. Default is false.

```shell
./craft formie/forms/import formie-contact-form.json
```

### Import All Forms
Import all Formie form JSON files from a folder.

Option | Description
--- | ---
`folderPath` | Optional path to look for JSON files. Defaults to the plugin's export folder.
`--create` | Whether to create new forms instead of updating existing ones. Default is false.

```shell
./craft formie/forms/import-all
```

## Submissions

### Re-save Submissions
Refer to the [Craft docs](https://craftcms.com/docs/4.x/console-commands.html#resave) on available options.

```shell
./craft resave/formie-submissions --form-id=1234 --update-search-index=1
```

### Run Integrations
For a provided submission, run the provided integration.

Option | Description
--- | ---
`--submission-id` | The submission ID(s) to use data for. Can be set to multiple comma-separated IDs.
`--integration` | The handle of the integration to trigger.

```shell
./craft formie/submissions/run-integration --submission-id=12345 --integration=mailchimp
```

### Send Email Notification
For a provided submission, send the provided notification.

Option | Description
--- | ---
`--submission-id` | The submission ID(s) to use data for. Can be set to multiple comma-separated IDs.
`--notification-id` | The ID of the notification to trigger.

```shell
./craft formie/submissions/send-notification --submission-id=12345 --notification-id=12
```


## Garbage Collection

### Delete Orphaned Fields
A cleanup tasks to ensure fields aren't orphaned. Not normally required.

```shell
./craft formie/gc/delete-orphaned-fields
```

### Prune Incomplete Submissions
Deletes any incomplete submissions that exceed the "Maximum Incomplete Submission Age" plugin setting.

```shell
./craft formie/gc/prune-incomplete-submissions
```

### Prune Data Retention Submissions
Deletes any submissions that exceed your data retention form settings.

```shell
./craft formie/gc/prune-data-retention-submissions
```

Each of the above commands are also run automatically through [Craft's Garbage Collection](https://craftcms.com/docs/4.x/gc.html), so there's no need to add these commands unless you want fine-grained control over when they run.

## Delete Submissions
You can bulk delete submissions with this command.

Option | Description
--- | ---
`--form-handle` | The form handle(s) to delete submissions from. Can be set to multiple comma-separated handles.
`--form-id` | The form ID(s) to delete submissions from. Can be set to multiple comma-separated IDs.
`--incomplete-only` | Whether to delete only incomplete submissions.
`--spam-only` | Whether to delete only spam submissions.

```shell
./craft formie/submissions/delete --form-handle=form1,anotherForm
```

## Delete Sent Notifications
You can bulk delete sent notifications with this command.

Option | Description
--- | ---
`--form-handle` | The form handle(s) to delete sent notifications for. Can be set to multiple comma-separated handles.
`--form-id` | The form ID(s) to delete sent notifications for. Can be set to multiple comma-separated IDs.

```shell
./craft formie/sent-notifications/delete --form-handle=form1,anotherForm
```

## Migration
You can run the migrations from either Sprout Forms or Freeform via the command line. This would be an ideal approach if you have a large number of submissions or complex forms to migrate.

### Migrate Sprout Forms

Option | Description
--- | ---
`--form-handle` | The Sprout Forms handle(s) to migrate. Can be set to multiple comma-separated handles. Omit to migrate all.

```shell
./craft formie/migrate/migrate-sprout-forms --form-handle=form1,anotherForm
```

### Migrate Freeform

Option | Description
--- | ---
`--form-handle` | The Freeform form handle(s) to migrate. Can be set to multiple comma-separated handles. Omit to migrate all.

```shell
./craft formie/migrate/migrate-freeform4 --form-handle=form1,anotherForm
```

```shell
./craft formie/migrate/migrate-freeform5 --form-handle=form1,anotherForm
```
