#!/bin/bash

echo "Starting job listing migrations: $(date)"
while true
do
  # Sleep for 15 minutes.
  sleep 900

  echo "Running job listing migrations: $(date)"

  # Reset stuck migrations every 30 minutes.
  drush migrate:import --tag helfi_rekry_jobs --no-progress --reset-threshold 1800 --verbose
done
