#!/bin/bash

echo "Starting job listing:changed migrations: $(date)"
while true
do
  # Run migrations every 15 minutes, reset stuck migrations every 12 hours.
  echo "Running job listing:changed migrations: $(date)"
  drush migrate:import helfi_rekry_jobs:changed --update --reset-threshold 43200 --interval 900
  echo "Running job listing:changed migrations (sv): $(date)"
  drush migrate:import helfi_rekry_jobs:changed_sv --update --reset-threshold 43200 --interval 900
  echo "Running job listing:changed migrations (en): $(date)"
  drush migrate:import helfi_rekry_jobs:changed_en --update --reset-threshold 43200 --interval 900

  # Sleep for 30 minutes.
  sleep 1800
done
