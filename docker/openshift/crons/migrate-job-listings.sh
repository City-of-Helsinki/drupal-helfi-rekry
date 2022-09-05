#!/bin/bash

echo "Starting job listing migrations: $(date)"

while true
do
  echo "Running job listing image migrations: $(date)"
  HELBIT_CLIENT_ID=helrekrymyst0422 drush migrate:import helfi_rekry_images:all

  echo "Running job listing video migrations: $(date)"
  HELBIT_CLIENT_ID=helrekrymyst0422 drush migrate:import helfi_rekry_videos:all

  echo "Running job listing task area migrations: $(date)"
  HELBIT_CLIENT_ID=helrekrymyst0422 drush migrate:import helfi_rekry_task_areas:all

  echo "Running job listing organization migrations: $(date)"
  HELBIT_CLIENT_ID=helrekrymyst0422 drush migrate:import helfi_rekry_organizations:all

  echo "Running job listing migrations: $(date)"
  HELBIT_CLIENT_ID=helrekrymyst0422 drush migrate:import helfi_rekry_jobs:all
  HELBIT_CLIENT_ID=helrekrymyst0422 drush migrate:import helfi_rekry_jobs:all_sv
  HELBIT_CLIENT_ID=helrekrymyst0422 drush migrate:import helfi_rekry_jobs:all_en

  # Sleep for 6 hours.
  sleep 21600
done
