#!/bin/bash

migrations=(
  "helfi_rekry_images"
  "helfi_rekry_videos"
  "helfi_rekry_task_areas:fi"
  "helfi_rekry_task_areas:sv"
  "helfi_rekry_task_areas:en"
  "helfi_rekry_organizations:fi"
  "helfi_rekry_organizations:sv"
  "helfi_rekry_organizations:en"
  "helfi_rekry_employments"
  "helfi_rekry_employment_types"
)

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
