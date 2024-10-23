#!/bin/bash

echo "Starting job listing:changed migrations: $(date)"
while true
do
  echo "Running job listing:changed migrations: $(date)"
  # Allow migrations to be run every 10 minutes, reset stuck migrations every 30 minutes.
  drush migrate:import helfi_rekry_jobs:changed --update --reset-threshold 1800 --interval 600 --no-progress

  # Sleep for 15 minutes.
  sleep 900
done
