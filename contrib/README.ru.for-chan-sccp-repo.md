# SCCP Manager для FreePBX (develop)

| [English](README.md) | [Русский](README.ru.md) | [Upstream sccp_manager](https://github.com/chan-sccp/sccp_manager) |

![Demo](https://github.com/chan-sccp/sccp_manager/raw/develop/.dok/image/Demo_1s5.gif)

- [Установка](#установка)
- [Требования](#требования)
- [Ссылки](#ссылки)
- [Wiki](https://github.com/chan-sccp/chan-sccp/wiki)

## Ссылки

[![Sccp Manager](https://img.shields.io/badge/SccpGUI-build-ff69b4.svg)](https://github.com/timspb/sccp_manager)  
[![Драйвер chan-sccp](https://img.shields.io/badge/chan--sccp-driver-green.svg)](https://github.com/timspb/chan-sccp)  
[![Документация Chan-SCCP](https://img.shields.io/badge/docs-wiki-blue.svg)](https://github.com/chan-sccp/chan-sccp/wiki)

Модуль FreePBX для управления инфраструктурой Asterisk–Cisco: провизионирование и администрирование Cisco IP-телефонов и внутренних номеров (SCCP), по аналогии с Cisco CallManager. Идея из [Cynjut/SCCP_Manager](https://github.com/Cynjut/SCCP_Manager), развитие — [PhantomVl/sccp_manager](https://github.com/PhantomVl/sccp_manager) и [chan-sccp/sccp_manager](https://github.com/chan-sccp/sccp_manager). sccp_manager работает вместе с [chan-sccp](https://github.com/chan-sccp/chan-sccp).

**Этот репозиторий (драйвер):** [timspb/chan-sccp](https://github.com/timspb/chan-sccp) — рабочая сборка драйвера.  
**Модуль GUI:** [timspb/sccp_manager](https://github.com/timspb/sccp_manager) — модуль FreePBX (PHP 8.3+).

SCCP Manager — свободное ПО. См. [COPYING](COPYING).

## Требования

- **GUI:** FreePBX 16 или 17  
- **PHP:** 8.3 или новее  
- **chan-sccp** 4.3.4+ (рекомендуется: [timspb/chan-sccp](https://github.com/timspb/chan-sccp))  
- Расширение PHP **zip** (под вашу версию PHP):

```bash
apt-get install php8.3-zip
```

### Что нужно установить

- **chan-sccp** 4.3.4+: [Сборка и установка](https://github.com/chan-sccp/chan-sccp/wiki/Building-and-Installation-Guide)  
  - Флаги configure: `./configure --enable-conference --enable-advanced-functions --enable-distributed-devicestate --enable-video`
- **TFTP** (например `/tftpboot`): [Настройка TFTP](https://github.com/chan-sccp/chan-sccp/wiki/setup-tftp-service)  
  - Шаблоны: `cp /usr/src/chan-sccp/conf/tftp/*.xml* /tftpboot/templates/`
- **DHCP**: [Настройка DHCP](https://github.com/chan-sccp/chan-sccp/wiki/setup-dhcp-service)

### Настройка

- [Установка FreePBX](http://wiki.freepbx.org/display/FOP/Install+FreePBX)
- [Настройка Chan-Sccp](https://github.com/chan-sccp/chan-sccp/wiki/How-to-setup-the-chan_sccp-Module)
- sccp_manager настраивает Asterisk realtime для chan-sccp. См. [Realtime Configuration](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).

## Установка

1. Войдите в FreePBX.
2. **Администрирование** → **Управление модулями** → **Загрузить модули**.
3. В **Скачать из интернета** вставьте один из URL:

**Этот форк (рекомендуется):**

```
https://github.com/timspb/sccp_manager/archive/refs/heads/develop.zip
```

**Upstream develop:**

```
https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip
```

4. **Скачать из интернета** → **Управление локальными модулями** → выберите **SCCP Manager** → **Установить** → **Выполнить**.
5. Подтвердите установку, закройте окно статуса.
6. **Применить конфигурацию** в FreePBX.
7. Дальше: [Using SCCP_Manager to Manage chan-sccp](https://github.com/chan-sccp/chan-sccp/wiki/Using-SCCP_Manager-to-Manage-chan-sccp).

### Обновление модуля

```bash
fwconsole ma upgrade sccp_manager
```

### Важно

- Модулю нужна база **Asterisk realtime**. См. [Realtime Configuration](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).
- Телефоны Cisco — прошивка **v8.1 или новее**.
- Локализация — через языковые профили Cisco.

### Чат

[![Gitter](https://badges.gitter.im/chan-sccp/chan-sccp.svg)](https://gitter.im/sccp_manager/community)
