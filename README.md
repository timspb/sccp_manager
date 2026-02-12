## Welcome to Chan_SCCP GUI Manager for FreePBX

| [English](README.md) | [Russian](README.ru.md) | [Upstream (chan-sccp)](https://github.com/chan-sccp/sccp_manager)

![Demo](https://github.com/chan-sccp/sccp_manager/raw/develop/.dok/image/Demo_1s5.gif)

  * [Installation](#installation)
  * [Prerequisites](#prerequisites)
  * [Links](#link)
  * [Wiki](https://github.com/chan-sccp/chan-sccp/wiki)

**This repository:** [timspb/sccp_manager](https://github.com/timspb/sccp_manager) — active development fork of chan-sccp/sccp_manager.  
Module version 17.0.1.0 (production-ready for FreePBX 16/17, PHP 8.2+).

## Link

[![Download Sccp-Manager](https://img.shields.io/badge/SccpGUI-build-ff69b4.svg)](https://github.com/timspb/sccp_manager/archive/refs/heads/develop.zip)
[![Chan-SCCP channel driver for Asterisk](https://img.shields.io/sourceforge/dt/chan-sccp-b.svg)](https://github.com/chan-sccp/chan-sccp/releases/latest)
[![Chan-SCCP Documentation](https://img.shields.io/badge/docs-wiki-blue.svg)](https://github.com/chan-sccp/chan-sccp/wiki)

This FreePBX module helps manage Asterisk–Cisco infrastructure: provisioning and administration of Cisco IP phones and extensions in a way similar to Cisco CallManager.

**Compatibility (develop branch):** FreePBX 16/17, Asterisk 21/22/23, PHP 8.2+, chan-sccp 4.3.5+.

The idea of creating a module is borrowed from [Cynjut/SCCP_Manager](https://github.com/Cynjut/SCCP_Manager), and was further developed by [PhantomVl](https://github.com/PhantomVl/sccp_manager) and [chan-sccp/sccp_manager](https://github.com/chan-sccp/sccp_manager). sccp_manager relies heavily on [chan-sccp](https://github.com/chan-sccp/chan-sccp).

SCCP Manager is free software. Please see the file COPYING for details.

This module will suit you if you are planning to migrate from CallManager to Asterisk (or have already done it). SCCP-Manager allows you to administer SCCP extensions and a wide range of Cisco phone types (including IP Communicator). You can control phone buttons (depending on the phone model) assigning multiple lines, speeddials and BLF's. You can use the driver functions "sccp_chain" from the GUI module.

### Wiki
More information and documentation: [SCCP Manager / chan-sccp Wiki](https://github.com/chan-sccp/chan-sccp/wiki).

## Prerequisites

Make sure you have the following installed on your system:

- **GUI:** FreePBX 16 or 17
- **PHP:** 8.2 or later (required by module dependencies)
- A working version of [chan-sccp](https://github.com/chan-sccp/chan-sccp)
- **PHP zip extension** (e.g. on Debian with PHP 8.2):
```bash
apt-get install php8.2-zip
```

### Requirements

- **chan-sccp** 4.3.4 or later (4.3.5+ recommended): [Building and Installation](https://github.com/chan-sccp/chan-sccp/wiki/Building-and-Installation-Guide)
  - sccp_manager expects these configure flags:
    ```bash
    ./configure --enable-conference --enable-advanced-functions --enable-distributed-devicestate --enable-video
    ```
- **TFTP** server (recommended under `/tftpboot/`): [Setup TFTP](https://github.com/chan-sccp/chan-sccp/wiki/setup-tftp-service)
  - Phone settings templates, e.g. from chan-sccp:
    ```bash
    cp /usr/src/chan-sccp/conf/tftp/*.xml* /tftpboot/templates/
    ```
- **DHCP** server: [Setup DHCP](https://github.com/chan-sccp/chan-sccp/wiki/setup-dhcp-service)

### Setup

- [Setting up FreePBX](http://wiki.freepbx.org/display/FOP/Install+FreePBX)
- [Setting up Chan-Sccp](https://github.com/chan-sccp/chan-sccp/wiki/How-to-setup-the-chan_sccp-Module)
- The sccp_manager module will automatically setup and configure the Asterisk realtime database for chan-sccp. See [Realtime Configuration](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).

## Installation

### How to install sccp_manager

1. Log in to FreePBX.
2. Go to **Admin → Module Admin**.
3. Click **Upload Modules**.
4. Enter one of the following URLs:

**Develop (recommended for FreePBX 16/17, PHP 8.2+)**

Compatible with FreePBX 16/17, Asterisk 21/22/23, and PHP 8.2+. Includes latest fixes and production-ready improvements (XSS escaping, i18n, logging).

_This is development software and may have issues._

```
https://github.com/timspb/sccp_manager/archive/refs/heads/develop.zip
```

**Stable (tagged release)**

```
https://github.com/timspb/sccp_manager/archive/refs/tags/v17.0.1.0.tar.gz
```

**Upstream (chan-sccp)**

```
https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip
```

5. Click **Download From Web**.
6. Click **Manage Local Modules**.
7. Find **SCCP Manager**, check **Install**, click **Process**.
8. Confirm installation, close Status window.
9. **Apply Config** in FreePBX.
10. Continue to [Using SCCP_Manager to Manage chan-sccp](https://github.com/chan-sccp/chan-sccp/wiki/Using-SCCP_Manager-to-Manage-chan-sccp).

### Module update

From the command line:

```bash
fwconsole ma upgrade sccp_manager
```

Or use **Admin → Module Admin** in the FreePBX GUI when an update is available.

### IMPORTANT NOTES

- This system assumes you are using the **Asterisk realtime database**. If not, set it up first: [Realtime Configuration](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).
- For Cisco phones to work correctly, provision them with firmware **v8.1 or higher**.
- You can use Cisco language profiles to switch the phones to your locale.

### Chat

[![Gitter](https://badges.gitter.im/chan-sccp/chan-sccp.svg)](https://gitter.im/sccp_manager/community)
