#!/bin/bash

ulimit -n 10000;
#ulimit -f unlimited;
php phing.phar release;
