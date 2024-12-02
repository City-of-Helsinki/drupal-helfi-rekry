# City of Helsinki - Rekry Drupal project

Rekry, which is short for recruitment in Finnish, is a site integrated with Helbit. It migrates job listings to the job
search found on the site and provides other recruitment information for the city of Helsinki.

## Environments

Env | Branch | Drush alias | URL
--- | ------ | ----------- | ---
development | * | - | http://helfi-rekry.docker.so/
production | main | @main | https://www.hel.fi/fi/avoimet-tyopaikat

## Requirements

You need to have these applications installed to operate on all environments:

- [Docker](https://github.com/druidfi/guidelines/blob/master/docs/docker.md)
- [Stonehenge](https://github.com/druidfi/stonehenge)
- For the new person: Your SSH public key needs to be added to servers

## Create and start the environment

For the first time (new project):

``
$ make new
``

And following times to start the environment:

``
$ make up
``

NOTE: Change these according of the state of your project.

## Login to Drupal container

This will log you inside the app container:

```
$ make shell
```

## Instance specific features

Some instance specific configuration can be found in Rekry-instance configuration page in the
`/admin/tools/rekry-content` url.

### Custom node types

#### Job listing (job_listing)

A _job listing_ is custom node type used to migrate job listings from Helbit, meaning all nodes are created
automatically. While you can create a _job listing_ node manually through the user interface, this is not the standard
workflow on the site. These listings appear in the `job_search` paragraph and a view called `of_interest`displayed in a
block on _job listing_ nodes. The _job listings_ utilize multiple taxonomies to categorize the content. Some job listings
include media such as images and videos and there is much processing going into these on the `helfi_rekry_content`
module. Read more about the [Helbit integration](#helbit-integration) that creates these nodes and the [Job search](#job-search-job_search)
feature that displays them below.

### Custom paragraphs

#### Job search (job_search)

_Job search_ is a paragraph with two modes. The first mode provides a few filters and a submit button that redirects to
the node specified in the _Search result page_ field, using the applied filters as parameters. If the
_Search result page_ field is not filled, the full _job search_ is displayed. _Job search_is a React-based search that
uses the (`job_listing_search`) view as a fallback when JavaScript is not enabled. All React searches are part of the
`hdbt` theme, where most related logic is also located. The _job search_ paragraph includes an editable title,
description, and the _Search result page_ field. There is also a saved search feature called _Hakuvahti_ embedded on the
_job search_. Read more about this feature on the [Hakuvahti section](#hakuvahti) of this document.

- React search code can be found under the `hdbt` theme [here](https://github.com/City-of-Helsinki/drupal-hdbt/tree/main/src/js/react/apps/job-search).
- Check the `hdbt_subtheme` preprocesses for _job search_ related configuration [here](https://github.com/City-of-Helsinki/drupal-helfi-rekry/tree/dev/public/themes/custom/hdbt_subtheme).
- Fallback view when JavaScript is not enabled can be found in the `/conf/cim` folder [here](https://github.com/City-of-Helsinki/drupal-helfi-rekry/blob/dev/conf/cmi/views.view.job_listing_search.yml).
- The saved search feature _Hakuvahti_ uses a separate server and the code related to it can be found [here](https://github.com/City-of-Helsinki/helfi-hakuvahti).

##### Common issues

Sometimes on local the search dropdowns don't have any content. In this case usually running the indexing helps:

1. Run `make shell` on the root of the project
2. Inside the shell run `drush sapi-rt; drush sapi-c; drush sapi-i; drush cr` to clear the Elastic index and reindex it
and clear Drupal caches after the indexing is done.
3. Now retry the search dropdowns and they should have options.

### Custom media types

#### Job listing image (job_listing_image)

_Job listing image_ is a media type that is used to save the imported images from Helbit. The original idea behind this
separate media type has perhaps been a way to separate images used for normal content and job listings.

### Custom roles

#### HR (hr)

User role for viewing and editing only the job listings on the site.

### Helbit integration

_Helbit_ is the source from which job listings are migrated to this instance. The `helfi_rekry_content` module handles
the migration and data processing. It retrieves job listing information from the API and saves it as job listing nodes
in the database. Media such as images and videos are processed and stored as media entities, with unsupported video
types being skipped. Categorization is achieved using taxonomy terms created from the API data, which are then linked
to the job listing nodes. The migrations run periodically as a cron jobs. Both published and unpublished job listings
from the API are migrated, with future publish dates scheduled accordingly. A separate cron job ensures scheduled job
listings are published for indexing.

- The `helfi_rekry_content` module code can be found from [here](https://github.com/City-of-Helsinki/drupal-helfi-rekry/tree/dev/public/modules/custom/helfi_rekry_content).
- The migration interval for the new job listings can be checked from the cron configuration [here](https://github.com/City-of-Helsinki/drupal-helfi-rekry/blob/dev/docker/openshift/crons/migrate-job-listings.sh).
- The migration interval for images, taxonomy terms, etc. is written on this cron configuration [here](https://github.com/City-of-Helsinki/drupal-helfi-rekry/blob/dev/docker/openshift/crons/migrate-helbit-data.sh).
- The scheduled publishing interval can be checked from this cron configuration [here](https://github.com/City-of-Helsinki/drupal-helfi-rekry/blob/dev/docker/openshift/crons/content-scheduler.sh).

### Testing on local

The `job_listing_images` migration requires Azure Blob storage to be configured. See [Azure FS module](https://github.com/City-of-Helsinki/drupal-module-helfi-azure-fs?tab=readme-ov-file#testing-on-local) for more information.

Modify/create `public/sites/default/local.settings.php` file with:
```php
$config['helfi_rekry_content.settings']['helbit_client_id'] = '[ copy value from HELBIT_CLIENT_ID environment variable ]';
```

### Hakuvahti

_Hakuvahti_ is feature of the [Job search](#job-search-job_search) that allows users to save their job search criteria. Users will
receive automatic email notifications whenever new job listings that match their criteria are posted on the site.

Hakuvahti consists of three main components: a Node.js server, `helfi_hakuvahti` custom module and a React part
integrated with the Job search. The Node.js server handles most of the heavy lifting, including sending emails. The
Drupal custom module manages communication between the Node.js server and the React form, which displays the
feature to users.

- The Drupal code for hakuvahti can be found from the `helfi_hakuvahti` custom module [here](https://github.com/City-of-Helsinki/drupal-helfi-rekry/tree/dev/public/modules/custom/helfi_hakuvahti).
- The hakuvahti Node.js server is on a separate repository [here](https://github.com/City-of-Helsinki/helfi-hakuvahti).
- The React part is under the Job search in `hdbt` theme _react_ folder. The functionality is written in [this file](https://github.com/City-of-Helsinki/drupal-hdbt/blob/main/src/js/react/apps/job-search/containers/SearchMonitorContainer.tsx).
- The React form has configurable texts that can be found from the Rekry instance configuration in
  `/admin/tools/rekry-content` url. You can translate these configurations for the three main languages using the
  _Translate_ tab.

#### How to enable Hakuvahti features on local

To enable Hakuvahti features for local development or usage, you need to:

1. Install [Hakuvahti Node.js server]((https://github.com/City-of-Helsinki/helfi-hakuvahti) locally.
2. Start the server on your local. Instructions are on the [Hakuvahti server README](https://github.com/City-of-Helsinki/helfi-hakuvahti?tab=readme-ov-file#installing-and-running-hakuvahti).
3. Now clear the caches on your Rekry instance, and you should be able to see the Hakuvahti on the Job search on you
local site.
4. To test the functionality, you should use the Mailpit running on your local in the `https://mailpit.docker.so/` url
to view the emails being sent by the feature.

#### Google indexing api automation (helfi_google_api-module)

Job listing urls are automatically sent to google indexing api
on publish and unpublish events, a request is sent to google to either index or deindex the url.

## Customizations

### Not part of global navigation

Unlike other instances the link to the instance is in the header top navigation and the main menu of the instance is not
part of the global navigation. There is however a link to this instance located under another instance's menu tree on
the global navigation too. The logic that dictates if the menu is added to the global navigation or not can be found
[here](https://github.com/City-of-Helsinki/drupal-module-helfi-navigation/blob/main/src/Plugin/rest/resource/GlobalMobileMenu.php).
