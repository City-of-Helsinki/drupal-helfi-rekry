#!/bin/bash

echo "Starting job listing migrations: $(date)"

function populate_variables {
  # Generate variables used to control which migrates needs
  # to be reset and which ones needs to be skipped based on
  # migrate status
  MIGRATE_STATUS=$(drush migrate:status --format=json)
  php ./docker/openshift/crons/migrate-status.php \
    helfi_rekry_images:all,helfi_rekry_videos:all,helfi_rekry_task_areas:all,helfi_rekry_organizations:all,helfi_rekry_jobs:all,helfi_rekry_jobs:all_sv,helfi_rekry_jobs:all_en \
    "$MIGRATE_STATUS" > /tmp/migrate-job-listings-source.sh \
    $1

  # Contains variables:
  # - $RESET_STATUS
  # - $SKIP_MIGRATE
  # Both contains a space separated list of migrates
  source /tmp/migrate-job-listings-source.sh
}

function reset_status {
  # Reset status of stuck migrations.
  for ID in $RESET_STATUS; do
    drush migrate:reset-status $ID
  done
}

function run_migrate {
  for ID in $SKIP_MIGRATE; do
    if [ "$ID" == "$1" ]; then
      return 1
    fi
  done
  return 0
}

# Populate variables for the first run after deploy and
# default migrate interval to 6 hours.
populate_variables 21600

# Set HELBIT API client ID.
export HELBIT_CLIENT_ID=helrekrymyst0422

while true
do
  # Reset stuck migrations.
  reset_status

  if run_migrate "helfi_rekry_images:all"; then
    echo "Running job listing image migrations: $(date)"
    drush migrate:import helfi_rekry_images:all
  fi

  if run_migrate "helfi_rekry_videos:all"; then
    echo "Running job listing video migrations: $(date)"
    drush migrate:import helfi_rekry_videos:all
  fi

  if run_migrate "helfi_rekry_task_areas:all"; then
    echo "Running job listing task area migrations: $(date)"
    drush migrate:import helfi_rekry_task_areas:all
  fi

  if run_migrate "helfi_rekry_organizations:all"; then
    echo "Running job listing organization migrations: $(date)"
    drush migrate:import helfi_rekry_organizations:all
  fi

  echo "Running job listing migrations: $(date)"
  if run_migrate "helfi_rekry_jobs:all"; then
    echo "Running job listing migrations: $(date)"
    drush migrate:import helfi_rekry_jobs:all
  fi

  if run_migrate "helfi_rekry_jobs:all_sv"; then
    echo "Running job listing migrations (sv): $(date)"
    drush migrate:import helfi_rekry_jobs:all_sv
  fi

  if run_migrate "helfi_rekry_jobs:all_en"; then
    echo "Running job listing migrations (en): $(date)"
    drush migrate:import helfi_rekry_jobs:all_en
  fi

  # Reset migrate status if migrate has been running for more
  # than 12 hours.
  populate_variables 43200
  # Never skip migrate after first time.
  SKIP_MIGRATE=

  # Sleep for 6 hours.
  sleep 21600
done
