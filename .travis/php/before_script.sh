#!/usr/bin/env sh

set -ev

vendor/bin/codecept clean --no-interaction
vendor/bin/codecept build --no-interaction
