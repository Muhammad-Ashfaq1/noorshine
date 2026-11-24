#!/usr/bin/env bash
set -euo pipefail

fail() {
  printf '%s\n' "$*" >&2
  exit 1
}

mode=${1:-check}
case "$mode" in
  check|apply) ;;
  *) fail 'Usage: bash scripts/deploy-theme.sh [check|apply]' ;;
esac

host=${NOORSHINE_SSH_HOST:?Set the SSH host from the NoorShine hPanel}
user=${NOORSHINE_SSH_USER:?Set the SSH username from the NoorShine hPanel}
port=${NOORSHINE_SSH_PORT:?Set the SSH port from the NoorShine hPanel}
fingerprint=${NOORSHINE_SSH_FINGERPRINT:?Set the ED25519 host fingerprint from the NoorShine hPanel}
key_file=${NOORSHINE_SSH_KEY_FILE:-"$HOME/.ssh/noorshine-deploy"}

[[ $host =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*$ ]] || fail 'Invalid SSH hostname or IPv4 address.'
[[ $user =~ ^[a-zA-Z0-9_][a-zA-Z0-9_-]*$ ]] || fail 'Invalid SSH username.'
[[ $port =~ ^[1-9][0-9]{0,4}$ ]] && (( port <= 65535 )) || fail 'Invalid SSH port.'
[[ $fingerprint =~ ^SHA256:[a-zA-Z0-9+/]{43}$ ]] || fail 'Use the SHA256 ED25519 host fingerprint from hPanel.'
[[ -r $key_file ]] || fail 'The NoorShine deployment key file could not be read.'

project=$(cd -- "$(dirname -- "$0")/.." && pwd)
theme="$project/wp-content/themes/noorshine"
version=$(awk '$1 == "Version:" { print $2; exit }' "$theme/style.css")
[[ $version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail 'The NoorShine theme version is missing or invalid.'
[[ -f "$theme/assets/css/main.css" ]] || fail 'The NoorShine theme stylesheet is missing.'

umask 077
scratch=$(mktemp -d /tmp/noorshine-deploy.XXXXXXXX)
trap 'rm -rf -- "$scratch"' EXIT

ssh-keyscan -T 10 -t ed25519 -p "$port" "$host" > "$scratch/host-key" 2>/dev/null ||
  fail 'Could not read the Hostinger SSH host key. Verify the SSH details and access in hPanel.'
actual_fingerprint=$(ssh-keygen -lf "$scratch/host-key" | awk '{ print $2 }')
[[ $actual_fingerprint == "$fingerprint" ]] ||
  fail 'The server host key does not match the ED25519 fingerprint from hPanel.'
awk '$2 == "ssh-ed25519" { print "noorshine-deploy", $2, $3 }' "$scratch/host-key" > "$scratch/known_hosts"
cp -- "$key_file" "$scratch/key"
chmod 600 "$scratch/key"
cat > "$scratch/config" <<EOF
Host noorshine-deploy
    HostName $host
    User $user
    Port $port
    HostKeyAlias noorshine-deploy
    HostKeyAlgorithms ssh-ed25519
    UserKnownHostsFile $scratch/known_hosts
    GlobalKnownHostsFile /dev/null
    StrictHostKeyChecking yes
    IdentityFile $scratch/key
    IdentitiesOnly yes
    BatchMode yes
    ConnectTimeout 15
    ServerAliveInterval 15
EOF

ssh -F "$scratch/config" noorshine-deploy sh -s -- "$mode" <<'REMOTE'
set -eu
cd "$HOME/domains/noorshine.pk/public_html"
theme=wp-content/themes/noorshine
if [ ! -f wp-load.php ] || [ ! -f "$theme/style.css" ]; then
  echo 'Expected a WordPress installation with the NoorShine theme already installed.' >&2
  exit 1
fi
if ! grep -Eq '^Theme Name:[[:space:]]*NoorShine[[:space:]]*$' "$theme/style.css" ||
  [ -L wp-content ] || [ -L wp-content/themes ] || [ -n "$(find "$theme" -type l -print -quit)" ]; then
  echo 'The installed theme path or metadata differs from the expected NoorShine installation.' >&2
  exit 1
fi
command -v rsync >/dev/null || { echo 'rsync is required on the hosting account.' >&2; exit 1; }
if [ "$1" = apply ]; then
  umask 077
  backup_root="$HOME/.noorshine-theme-backups"
  mkdir -p "$backup_root"
  backup=$(mktemp -d "$backup_root/theme.XXXXXXXX")
  cp -a "$theme" "$backup/noorshine"
  printf 'Theme backup: %s\n' "$backup/noorshine"
fi
REMOTE

rsync_options=(-rz --checksum --delay-updates --itemize-changes --exclude='.*')
if [[ $mode == check ]]; then
  rsync_options+=(--dry-run)
fi
rsync "${rsync_options[@]}" -e "ssh -F $scratch/config" "$theme/" \
  noorshine-deploy:domains/noorshine.pk/public_html/wp-content/themes/noorshine/

if [[ $mode == apply ]]; then
  curl --fail --silent --show-error --max-time 30 \
    "https://noorshine.pk/wp-content/themes/noorshine/assets/css/main.css?ver=$version" \
    --output "$scratch/live.css"
  cmp -s "$theme/assets/css/main.css" "$scratch/live.css" ||
    fail 'Files copied, but the public stylesheet differs. Check the WordPress or Hostinger cache.'
  printf 'NoorShine theme %s is deployed and the public stylesheet matches.\n' "$version"
else
  printf 'Dry run complete for NoorShine theme %s.\n' "$version"
fi
