#!/usr/bin/env sh

set -ev

phpenv config-rm xdebug.ini 2>/dev/null || :
