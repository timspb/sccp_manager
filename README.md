# SCCP Manager for FreePBX

| [English](README.md) | [Russian](README.ru.md) | [Upstream (chan-sccp)](https://github.com/chan-sccp/sccp_manager) |

FreePBX module for managing Asterisk–Cisco infrastructure: provisioning and administration of Cisco IP phones and extensions (SCCP) with [chan-sccp](https://github.com/chan-sccp/chan-sccp).

**This fork:** [timspb/sccp_manager](https://github.com/timspb/sccp_manager) ← [chan-sccp/sccp_manager](https://github.com/chan-sccp/sccp_manager).

## Requirements

- **FreePBX** 16 or 17  
- **PHP** 8.2+  
- **Asterisk** 21 / 22 / 23  
- **chan-sccp** 4.3.5+  
- PHP extension **zip**

```bash
apt-get install php8.2-zip
```

- **TFTP** (e.g. `/tftpboot`), **DHCP**. See [chan-sccp wiki](https://github.com/chan-sccp/chan-sccp/wiki).

## Installation

1. FreePBX → **Admin** → **Module Admin** → **Upload Modules**.
2. In **Download From Web** use one of:

**This fork (develop):**

```
https://github.com/timspb/sccp_manager/archive/refs/heads/develop.zip
```

**Upstream develop:**

```
https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip
```

3. **Download From Web** → **Manage Local Modules** → select **SCCP Manager** → **Install** → **Process**.
4. **Apply Config** in FreePBX.

Or clone and symlink (for development):

```bash
cd /var/www/html/admin/modules
git clone https://github.com/timspb/sccp_manager.git
# or: git clone https://github.com/chan-sccp/sccp_manager.git
fwconsole ma install sccp_manager
```

## Update

```bash
fwconsole ma upgrade sccp_manager
```

## Firmware / provisioner

The module can fetch firmware and locale files from [dkgroot/provision_sccp](https://github.com/dkgroot/provision_sccp). If downloads fail:

- **Permissions:** TFTP root (e.g. `/tftpboot`) and `admin/modules/sccp_manager/firmware` must be writable by the web server user (e.g. `asterisk`):

  ```bash
  sudo chown -R asterisk:asterisk /tftpboot
  ```

- **Connectivity:** Server must reach `https://github.com` (e.g. `curl -I https://github.com`).

- **Check script:**

  ```bash
  bash /var/www/html/admin/modules/sccp_manager/contrib/check_provisioner_env.sh /tftpboot
  ```

## Links

- [Chan-SCCP](https://github.com/chan-sccp/chan-sccp) — SCCP channel driver for Asterisk  
- [chan-sccp Wiki](https://github.com/chan-sccp/chan-sccp/wiki)  
- [Realtime Configuration](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration)  
- [Gitter](https://gitter.im/sccp_manager/community)

## License

GPL. See [COPYING](COPYING).
