#!/bin/sh
../vendor/bin/codecept build
../vendor/bin/codecept run

# Run single tests:
# ../vendor/bin/codecept run unit codeception/unit/models/HTMLNormalizeTest.php
# ../vendor/bin/codecept run acceptance MotionCreateCept

