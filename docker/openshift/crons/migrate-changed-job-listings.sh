#!/bin/bash

echo "Starting job listing:changed migrations: $(date)"

function populate_variables {
  # Generate variables used to control which migrates needs
  # to be reset and which ones needs to be skipped based on
  # migrate status
  MIGRATE_STATUS=$(drush migrate:status --format=json)
  php ./docker/openshift/crons/migrate-status.php \
    helfi_rekry_jobs:changed,helfi_rekry_jobs:changed_sv,helfi_rekry_jobs:changed_en \
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

while true
do
  # Reset stuck migrations.
  reset_status

  echo "Running job listing:changed migrations: $(date)"
  if run_migrate "helfi_rekry_jobs:changed"; then
    echo "Running job listing:changed migrations: $(date)"
    drush migrate:import helfi_rekry_jobs:changed --update
  fi

  if run_migrate "helfi_rekry_jobs:changed_sv"; then
    echo "Running job listing:changed migrations (sv): $(date)"
    drush migrate:import helfi_rekry_jobs:changed_sv --update
  fi

  if run_migrate "helfi_rekry_jobs:changed_en"; then
    echo "Running job listing:changed migrations (en): $(date)"
    drush migrate:import helfi_rekry_jobs:changed_en --update
  fi

  # Reset migrate status if migrate has been running for more
  # than 12 hours.
  populate_variables 43200
  # Never skip migrate after first time.
  SKIP_MIGRATE=

  # Sleep for ½ hour.
  sleep 1800
done
