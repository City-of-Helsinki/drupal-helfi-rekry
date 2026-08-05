#!/bin/bash

echo "Starting helbit migrations: $(date)"
while true
do
  echo "Running helbit migrations: $(date)"

  # Sleep for 3 hours.
  sleep 10800

  # Reset stuck migrations every 30 minutes.
  drush migrate:import --tag helfi_rekry_taxonomies --no-progress --reset-threshold 1800 --verbose

  drush helfi-rekry-content:clean-expired-listings
done
