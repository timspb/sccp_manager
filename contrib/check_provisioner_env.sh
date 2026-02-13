#!/bin/sh
# Check permissions and connectivity for Provision_Sccp firmware downloads.
# Run as root or as the web server user (e.g. asterisk, www-data) to test write access.
# Usage: ./check_provisioner_env.sh [tftpboot_path]

set -e
TFTP_ROOT="${1:-/tftpboot}"
MODULE_ROOT="$(dirname "$(dirname "$(dirname "$(readlink -f "$0")")")"
FIRMWARE_DIR="${MODULE_ROOT}/firmware"
PROVISIONER_XML="https://github.com/dkgroot/provision_sccp/raw/master/tools/tftpbootFiles.xml"
GITHUB_HTTPS="https://github.com"

echo "=== Sccp_manager provisioner environment check ==="
echo "TFTP root:    $TFTP_ROOT"
echo "Module root:  $MODULE_ROOT"
echo "Firmware dir: $FIRMWARE_DIR"
echo ""

# Permissions: writable by current user
check_writable() {
    _dir="$1"
    _name="$2"
    if [ ! -d "$_dir" ]; then
        echo "[CHECK] $_name: directory does not exist: $_dir"
        echo "        Create it and set owner to asterisk (or web server user): sudo mkdir -p $_dir && sudo chown asterisk:asterisk $_dir"
        return 1
    fi
    if [ -w "$_dir" ]; then
        echo "[OK]     $_name: writable ($_dir)"
        return 0
    else
        echo "[FAIL]   $_name: not writable by $(whoami) ($_dir)"
        echo "        Fix: sudo chown -R asterisk:asterisk $_dir  (or www-data, depending on your setup)"
        return 1
    fi
}

# Connectivity: curl to URL
check_connectivity() {
    _url="$1"
    _name="$2"
    if curl -fsSL --connect-timeout 10 --max-time 30 -o /dev/null "$_url" 2>/dev/null; then
        echo "[OK]     $_name: reachable ($_url)"
        return 0
    else
        echo "[FAIL]   $_name: unreachable or error ($_url)"
        echo "        Check: curl -v $_url"
        return 1
    fi
}

FAIL=0
check_writable "$TFTP_ROOT" "TFTP root" || FAIL=1
check_writable "$FIRMWARE_DIR" "Module firmware dir" || true   # optional; TFTP is main
check_connectivity "$GITHUB_HTTPS" "GitHub (HTTPS)" || FAIL=1
check_connectivity "$PROVISIONER_XML" "Provision_Sccp master XML" || FAIL=1

echo ""
if [ "$FAIL" -eq 0 ]; then
    echo "All checks passed. Provisioner downloads should work."
else
    echo "Some checks failed. Ensure:"
    echo "  1) $TFTP_ROOT (and optionally $FIRMWARE_DIR) are writable by the user that runs the web server (e.g. asterisk)."
    echo "  2) Server can reach github.com (no firewall/proxy blocking HTTPS)."
    echo "  3) If using a proxy, set HTTP_PROXY/HTTPS_PROXY or PHP/cURL proxy settings."
fi
exit "$FAIL"
