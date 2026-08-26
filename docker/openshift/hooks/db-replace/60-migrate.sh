#!/bin/sh

# Run the job migration.
drush migrate:import helfi_rekry_jobs --no-progress --update
