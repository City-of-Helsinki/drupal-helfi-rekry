#!/bin/bash

migrations=(
  "helfi_rekry_images:all"
  "helfi_rekry_videos:all"
  "helfi_rekry_task_areas:fi"
  "helfi_rekry_task_areas:sv"
  "helfi_rekry_task_areas:en"
  "helfi_rekry_organizations:fi"
  "helfi_rekry_organizations:sv"
  "helfi_rekry_organizations:en"
  "helfi_rekry_employments:all"
  "helfi_rekry_employment_types:all"
  "helfi_rekry_jobs:all"
)

echo "Starting job listing migrations: $(date)"
while true
do
  echo "Running job listing migrations: $(date)"

  for migration in "${migrations[@]}"; do
    # Allow migrations to be run every 1.5 hours, reset stuck migrations every 12 hours.
    drush migrate:import "$migration" --reset-threshold 43200 --interval 5400 --no-progress
  done

  drush helfi-rekry-content:clean-expired-listings

  # Sleep for 3 hours.
  sleep 10800
done
