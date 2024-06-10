#!/bin/bash

echo "Starting job listing migrations: $(date)"
while true
do
  echo "Running job listing migrations: $(date)"
  # Allow migrations to be run every 1.5 hours, reset stuck migrations every 12 hours.
  drush migrate:import helfi_rekry_images:all --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_videos:all --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_task_areas:all --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_task_areas:all_sv --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_task_areas:all_en --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_organizations:all --update --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_organizations:all_sv --update --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_organizations:all_en --update --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_employments --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_employment_types --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_jobs:all --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_jobs:all_sv --reset-threshold 43200 --interval 5400 --no-progress
  drush migrate:import helfi_rekry_jobs:all_en --reset-threshold 43200 --interval 5400 --no-progress

  drush helfi-rekry-content:clean-expired-listings

  # Sleep for 3 hours.
  sleep 10800
done
