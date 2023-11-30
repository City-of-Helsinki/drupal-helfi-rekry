#!/bin/bash

echo "Starting job listing:changed migrations: $(date)"
while true
do
  echo "Running job listing:changed migrations: $(date)"
  # Allow migrations to be run every 15 minutes, reset stuck migrations every 12 hours.
  drush migrate:import helfi_rekry_jobs:changed --update --reset-threshold 43200 --interval 900
  drush migrate:import helfi_rekry_jobs:changed_sv --update --reset-threshold 43200 --interval 900
  drush migrate:import helfi_rekry_jobs:changed_en --update --reset-threshold 43200 --interval 900

  # Sleep for 30 minutes.
  sleep 1800
done
