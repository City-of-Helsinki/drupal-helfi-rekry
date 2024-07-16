# City of Helsinki - Rekry Drupal project

Rekry, which is short for recruitment in Finnish, is a site integrated with Helbit. It migrates job listings to the job
search found on the site and provides other recruitment information for the city of Helsinki.

## Environments

Env | Branch | Drush alias | URL
--- | ------ | ----------- | ---
development | * | - | http://helfi-rekry.docker.so/
production | main | @main | TBD

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
block on _job listing_ nodes. The _job listings_ utilize multiple taxonomies to categorize the content. Read more about
the [Helbit integration](#helbit) that creates these nodes and the [Job search](#job-search) feature that displays them below.

### Custom paragraphs

#### <a name="job-search"></a>Job search (job_search)

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

### Custom media type

#### Job listing image (job_listing_image)

_Job listing image_ is a media type that is used to save the imported images from Helbit. The original idea behind this
separate media type has perhaps been a way to separate images used for normal content and job listings.

## Customizations

### <a name="helbit"></a>Helbit integration

### <a name="hakuvahti"></a>Hakuvahti

#### Enable Hakuvahti features

To enable [Hakuvahti features](https://github.com/City-of-Helsinki/helfi-hakuvahti) for local development or usage, you need to first install Hakuvahti and then enable
Hakuvahti network in compose.yaml.

These lines are commented out with comment `# Uncomment to enable Hakuvahti:`

Specifically what is commented out: `HAKUVAHTI_URL` environment variable, `helfi-hakuvahti_helfi-hakuvahti-network` for
`app` and `elastic` containers. Finally it needs to be listed under `networks`. This enables Hakuvahti server to access
ElasticSearch.
