#!/bin/bash
ulimit -n 100000 && ulimit -f unlimited && php phing.phar release