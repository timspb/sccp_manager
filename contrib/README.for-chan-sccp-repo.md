# Welcome to SCCP Manager for FreePBX (develop)

| [English](README.md) | [Russian](README.ru.md) | [Upstream sccp_manager](https://github.com/chan-sccp/sccp_manager) |

![Demo](https://github.com/chan-sccp/sccp_manager/raw/develop/.dok/image/Demo_1s5.gif)

- [Installation](#installation)
- [Prerequisites](#prerequisites)
- [Links](#links)
- [Wiki](https://github.com/chan-sccp/chan-sccp/wiki)

## Link

[![Sccp Manager](https://img.shields.io/badge/SccpGUI-build-ff69b4.svg)](https://github.com/timspb/sccp_manager)  
[![Chan-SCCP driver](https://img.shields.io/badge/chan--sccp-driver-green.svg)](https://github.com/timspb/chan-sccp)  
[![Chan-SCCP Documentation](https://img.shields.io/badge/docs-wiki-blue.svg)](https://github.com/chan-sccp/chan-sccp/wiki)

This FreePBX module helps manage Asterisk–Cisco infrastructure: provisioning and administration of Cisco IP phones and extensions (SCCP) in a similar way to Cisco CallManager. The idea comes from [Cynjut/SCCP_Manager](https://github.com/Cynjut/SCCP_Manager) and was developed by [PhantomVl/sccp_manager](https://github.com/PhantomVl/sccp_manager) and [chan-sccp/sccp_manager](https://github.com/chan-sccp/sccp_manager). sccp_manager relies on [chan-sccp](https://github.com/chan-sccp/chan-sccp).

**This repo (chan-sccp):** [timspb/chan-sccp](https://github.com/timspb/chan-sccp) — working driver.  
**GUI module:** [timspb/sccp_manager](https://github.com/timspb/sccp_manager) — FreePBX module (PHP 8.3+).

SCCP Manager is free software. See [COPYING](COPYING).

## Prerequisites

- **GUI:** FreePBX 16 or 17  
- **PHP:** 8.3 or later  
- **chan-sccp** 4.3.4+ (recommended: [timspb/chan-sccp](https://github.com/timspb/chan-sccp))  
- PHP extension **zip** (match your PHP version):

```bash
apt-get install php8.3-zip
```

### Requirements

- **chan-sccp** 4.3.4+: [Building and Installation](https://github.com/chan-sccp/chan-sccp/wiki/Building-and-Installation-Guide)  
  - Configure flags: `./configure --enable-conference --enable-advanced-functions --enable-distributed-devicestate --enable-video`
- **TFTP** (e.g. `/tftpboot`): [Setup TFTP](https://github.com/chan-sccp/chan-sccp/wiki/setup-tftp-service)  
  - Templates: `cp /usr/src/chan-sccp/conf/tftp/*.xml* /tftpboot/templates/`
- **DHCP**: [Setup DHCP](https://github.com/chan-sccp/chan-sccp/wiki/setup-dhcp-service)

### Setup

- [Setting up FreePBX](http://wiki.freepbx.org/display/FOP/Install+FreePBX)
- [Setting up Chan-Sccp](https://github.com/chan-sccp/chan-sccp/wiki/How-to-setup-the-chan_sccp-Module)
- sccp_manager configures Asterisk realtime for chan-sccp. See [Realtime Configuration](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).

## Installation

1. Log in to FreePBX.
2. Go to **Admin** → **Module Admin** → **Upload Modules**.
3. In **Download From Web** enter one of:

**This fork (recommended):**

```
https://github.com/timspb/sccp_manager/archive/refs/heads/develop.zip
```

**Upstream develop:**

```
https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip
```

4. Click **Download From Web** → **Manage Local Modules** → select **SCCP Manager** → **Install** → **Process**.
5. Confirm installation, close Status window.
6. **Apply Config** in FreePBX.
7. Continue to [Using SCCP_Manager to Manage chan-sccp](https://github.com/chan-sccp/chan-sccp/wiki/Using-SCCP_Manager-to-Manage-chan-sccp).

### Module update

```bash
fwconsole ma upgrade sccp_manager
```

### IMPORTANT NOTES

- This module requires the **Asterisk realtime database**. See [Realtime Configuration](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).
- Cisco phones should use firmware **v8.1 or higher**.
- You can use Cisco language profiles for localization.

### Chat

[![Gitter](https://badges.gitter.im/chan-sccp/chan-sccp.svg)](https://gitter.im/sccp_manager/community)
