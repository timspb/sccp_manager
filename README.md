# SCCP Manager

> FreePBX module for managing Cisco IP phones and SCCP extensions with Asterisk and [chan-sccp](https://github.com/chan-sccp/chan-sccp). Provisioning, buttons, BLF, multiple lines.

[![English](https://img.shields.io/badge/README-English-blue)](README.md) [![Русский](https://img.shields.io/badge/README-Русский-green)](README.ru.md) [![Upstream](https://img.shields.io/badge/upstream-chan--sccp%2Fsccp__manager-lightgrey)](https://github.com/chan-sccp/sccp_manager)

**Repo:** [timspb/sccp_manager](https://github.com/timspb/sccp_manager) ← [chan-sccp/sccp_manager](https://github.com/chan-sccp/sccp_manager)  
**Driver (working build):** [timspb/chan-sccp](https://github.com/timspb/chan-sccp)

---

## Requirements

| Component | Version |
|-----------|---------|
| FreePBX | 16 or 17 |
| PHP | 8.3+ |
| Asterisk | 21 / 22 / 23 |
| chan-sccp | 4.3.5+ |
| PHP extension | zip |

```bash
apt-get install php8.3-zip
```

**TFTP** (e.g. `/tftpboot`) and **DHCP** required. See [chan-sccp Wiki](https://github.com/chan-sccp/chan-sccp/wiki).

---

## Installation

1. FreePBX → **Admin** → **Module Admin** → **Upload Modules**.
2. In **Download From Web** paste:

```
https://github.com/timspb/sccp_manager/archive/refs/heads/develop.zip
```

3. **Download From Web** → **Manage Local Modules** → **SCCP Manager** → **Install** → **Process**.
4. **Apply Config**.

From shell (for development):

```bash
cd /var/www/html/admin/modules
git clone https://github.com/timspb/sccp_manager.git
fwconsole ma install sccp_manager
```

## Update

```bash
fwconsole ma upgrade sccp_manager
```

---

## Deployment (what install does)

When you run **Install** in Module Admin, the module:

1. **Checks chan-sccp** — Must be installed and running (Asterisk loads it). If not, install stops.
2. **Backup** — Zips `extensions.conf`, `extconfig`, `res_*`, `sccp*.conf` and a DB dump under `ASTETCDIR`.
3. **DB schema** — Creates/updates tables: `sccpdevice`, `sccpline`, `sccpdevmodel`, `sccpuser`, `sccpbuttonconfig`, `sccpsettings`. Drops old **tables** `sccpdeviceconfig` / `sccplineconfig` if present, then creates them as **VIEWs** (realtime for chan-sccp).
4. **Realtime** — Writes `extconfig` so chan-sccp uses `sccpdeviceconfig` and `sccplineconfig`; ensures `res_config_mysql.conf` (or `res_mysql.conf`) has the DB section.
5. **Driver** — Copies `sccp_manager/sccpManClasses/Sccp.class.php.v*` into FreePBX core drivers so Devices see SCCP.
6. **TFTP** — Detects TFTP root (e.g. `/tftpboot`), writes rewrite rules, saves paths to `sccpsettings`. If TFTP is down or root not found, install stops.
7. **masterFilesStructure.xml** — Fetched from provisioner into TFTP root; on failure, installs a local copy from `contrib/`.

After install: **Apply Config** in FreePBX, then configure phones and lines in **SCCP Connectivity**.

---

## Firmware / provisioner

The module fetches firmware and locale files from [dkgroot/provision_sccp](https://github.com/dkgroot/provision_sccp). Files live under `tftpboot/firmware/<model>/`, e.g. [7975](https://github.com/dkgroot/provision_sccp/tree/master/tftpboot/firmware/7975) has `SCCP75.9-4-2SR3-1S.loads`. If downloads give **0 KB files** (redirect/connectivity), the code now uses `raw.githubusercontent.com` and rejects 0-byte firmware. If downloads still fail:

- **Permissions:** `/tftpboot` and `admin/modules/sccp_manager/firmware` writable by the web server user (e.g. `asterisk`):  
  `sudo chown -R asterisk:asterisk /tftpboot`
- **Connectivity:** Server can reach `https://github.com` (`curl -I https://github.com`).
- **Check script:**  
  `bash .../sccp_manager/contrib/check_provisioner_env.sh /tftpboot`
- **Manual download** (example for 7975):  
  `wget -O /tftpboot/firmware/7975/SCCP75.9-4-2SR3-1S.loads "https://raw.githubusercontent.com/dkgroot/provision_sccp/master/tftpboot/firmware/7975/SCCP75.9-4-2SR3-1S.loads"`

---

## Database (chan_sccp realtime)

chan_sccp reads devices from MySQL via **extconfig** (`sccpdevice=mysql,asterisk,sccpdeviceconfig`). The module provides a **VIEW** `sccpdeviceconfig` (data from `sccpdevice` + `sccpbuttonconfig`), not a table. If `sccpdeviceconfig` was ever created as a **table** (e.g. by an old script), chan_sccp would see 0 rows and reject with "device unknown". On install/upgrade the module now runs `DROP TABLE IF EXISTS sccpdeviceconfig` before creating the view, so the view is always correct. If you see "registration reject device unknown" but the device exists in `sccpdevice`, check that `sccpdeviceconfig` is a view: `SHOW FULL TABLES WHERE Table_type = 'VIEW';` and that `SELECT name FROM sccpdeviceconfig;` returns your devices.

---

## Links

- [timspb/chan-sccp](https://github.com/timspb/chan-sccp) — driver (this fork)
- [chan-sccp/chan-sccp](https://github.com/chan-sccp/chan-sccp) — upstream
- [Wiki](https://github.com/chan-sccp/chan-sccp/wiki) · [Realtime](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration) · [Gitter](https://gitter.im/sccp_manager/community)

**License:** GPL. See [COPYING](COPYING).
