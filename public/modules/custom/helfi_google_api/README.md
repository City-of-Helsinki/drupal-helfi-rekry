# Google indexing api

The module handles job listing indexing and deindexing using API provided by Google. Module requires an api key to work.
Api key is only set to production environment since Google doesn't provide testing environment.

* `drupal/scheduler` -module events are used to trigger the indexing requests.
* `google/apiclient` -library is used to handle the communication with Google api.

Api documentation: https://developers.google.com/search/apis/indexing-api/v3/quickstart
Google library: https://github.com/googleapis/google-api-php-client
Api key: Check keyvault

## Development / local testing

### API KEY
You must set the api key in local.settings.php in order to use the module. Without it, the feature won't do anything at all.
Api key is set to local.settings.php like this: `$config['helfi_google_api.settings']['indexing_api_key'] = '{}'`
Instead of empty json object you can get the correct key from Keyvault.

### Indexing requests

The indexing api only allows sending urls pointing to hel.fi domain. Therefore you can properly test the features
on production environment. You can send production urls to indexing api from local environment if you have
the auth key set properly.

Sending local url to google indexing api results in 4xx error.


## Requests

### Indexing

* Indexing request is tied to scheduler event
* A temporary redirect is created for the entity that is used for the indexing.

### Deindexing

* Dendexing request is tied to scheduler event
* The temporary redirect is removed when deindexing is done.

### Status check

* You can send a status request to google api to find out if an URL has been indexed or deleted throuhg the API




