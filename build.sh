#!/bin/bash

ulimit -n 10000;
#ulimit -f unlimited;
composer update --no-dev;
php phing.phar release;
composer update;
