#!/bin/bash
set -e

# Force-disable conflicting modules to prevent Railway injection errors
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true

# Explicitly ensure only pre-fork is running for PHP
a2enmod mpm_prefork

# Hand back control to the default Apache container process
exec apache2-foreground