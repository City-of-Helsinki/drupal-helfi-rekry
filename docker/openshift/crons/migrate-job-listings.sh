#!/bin/bash

migrations=(
  "helfi_rekry_jobs"
)

echo "Starting job listing migrations: $(date)"
while true
do
  echo "Running job listing migrations: $(date)"

  for migration in "${migrations[@]}"; do
    # Allow migrations to be run every 10 minutes, reset stuck migrations every 30 minutes.
    drush migrate:import "$migration" --reset-threshold 1800 --interval 600 --no-progress --update
  done

  # Sleep for 15 minutes.
  sleep 900
done
