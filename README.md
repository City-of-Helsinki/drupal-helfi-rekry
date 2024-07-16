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
the Helbit integration that creates these nodes and the `job_search` feature that displays them below.

### Custom paragraphs

#### Job search (job_search)

Job search is a React search that uses views listing (`job_listing_search`) as a fallback when JavaScript is not
enabled. All React searches are found in the `hdbt` theme, so most of the related logic is also found there.

### Custom media type

#### Job listing image (job_listing_image)

## Customizations

### Helbit integration

### Hakuvahti

#### Enable Hakuvahti features

To enable [Hakuvahti features](https://github.com/City-of-Helsinki/helfi-hakuvahti) for local development or usage, you need to first install Hakuvahti and then enable
Hakuvahti network in compose.yaml.

These lines are commented out with comment `# Uncomment to enable Hakuvahti:`

Specifically what is commented out: `HAKUVAHTI_URL` environment variable, `helfi-hakuvahti_helfi-hakuvahti-network` for
`app` and `elastic` containers. Finally it needs to be listed under `networks`. This enables Hakuvahti server to access
ElasticSearch.
