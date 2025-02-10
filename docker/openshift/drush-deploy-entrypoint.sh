#!/bin/bash

source /init.sh

echo "Starting migration: $(date)"

drush state:set system.maintenance_mode 1 --input-format=integer
drush helfi:pre-deploy || true
drush deploy
drush helfi:post-deploy || true
drush state:set system.maintenance_mode 0 --input-format=integer
