# City of Helsinki - Rekry Drupal 9 project

Description of your project.

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

## Enable Hakuvahti features

To enable [Hakuvahti features](https://github.com/City-of-Helsinki/helfi-hakuvahti) for local development or usage, you need to first install Hakuvahti and then enable Hakuvahti network in compose.yaml.

These lines are commented out with comment `# Uncomment to enable Hakuvahti:`

Specifically what is commented out: `HAKUVAHTI_URL` environment variable, `helfi-hakuvahti_helfi-hakuvahti-network` for `app` and `elastic` containers. Finally it needs to be listed under `networks`. This enables Hakuvahti server to access ElasticSearch.
