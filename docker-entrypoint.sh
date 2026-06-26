set -e

a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true

a2enmod mpm_prefork

exec apache2-foreground