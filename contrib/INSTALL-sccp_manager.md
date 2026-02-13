# Установка sccp_manager

Инструкция по установке модуля FreePBX **SCCP Manager** из репозитория [chan-sccp/sccp_manager](https://github.com/chan-sccp/sccp_manager) для управления Cisco SCCP-телефонами через веб-интерфейс.

## Требования

- **FreePBX:** 16 или 17 (установлен и настроен)
- **chan_sccp:** уже установлен и загружен в Asterisk (см. [INSTALL-chan-sccp.md](INSTALL-chan-sccp.md))
- **PHP:** 8.2 или 8.3 (с расширениями zip, mysql/pdo_mysql, xml и т.д., как у стандартной установки FreePBX)
- **TFTP:** каталог `/tftpboot` (рекомендуется), права на запись для пользователя веб-сервера (обычно `asterisk`)

## Вариант A: Установка через веб-интерфейс FreePBX

1. Скачайте архив модуля:
   - **Develop (последние изменения):**  
     https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip
   - **Стабильный тег (релиз):**  
     https://github.com/chan-sccp/sccp_manager/archive/refs/tags/v17.0.1.1.zip

2. Войдите в FreePBX: **Admin → Module Admin**.

3. **Upload Modules** → в поле URL вставьте одну из ссылок выше → **Download From Web**.

4. **Manage Local Modules** → найдите **SCCP Manager** → отметьте **Install** → **Process**.

5. После установки: **Apply Config**, при необходимости перезагрузите конфигурацию (`fwconsole reload`).

Если установка прерывается из-за проверки TFTP, используйте [Вариант B](#вариант-b-установка-из-командной-строки) с флагом `--force`.

---

## Вариант B: Установка из командной строки

### 1. Скачивание и размещение модуля

```bash
cd /tmp
curl -sL -o sccp_manager.zip "https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip"
unzip -q sccp_manager.zip
cp -a sccp_manager-develop /var/www/html/admin/modules/sccp_manager
chown -R asterisk:asterisk /var/www/html/admin/modules/sccp_manager
```

Путь к модулям может отличаться (например, `/var/www/html/admin/modules`). Уточните по вашей установке FreePBX.

### 2. Требование версии PHP (при необходимости)

Если в `module.xml` указано `<phpversion>8.3</phpversion>`, а у вас только PHP 8.2 (и ionCube настроен для 8.2), можно временно изменить в файле:

```bash
sed -i 's/<phpversion>8.3<\/phpversion>/<phpversion>8.2<\/phpversion>/' \
  /var/www/html/admin/modules/sccp_manager/module.xml
```

### 3. Установка модуля

```bash
fwconsole ma install sccp_manager --force
```

Флаг `--force` пропускает проверку TFTP, если сервис ещё не настроен.

### 4. Права на каталог TFTP

Чтобы модуль мог записывать `masterFilesStructure.xml` и другие файлы в `/tftpboot`:

```bash
chown -R asterisk:asterisk /tftpboot
```

При необходимости создайте каталог и шаблоны:

```bash
mkdir -p /tftpboot/templates
cp /usr/src/chan-sccp/conf/tftp/*.xml* /tftpboot/templates/
chown -R asterisk:asterisk /tftpboot
```

### 5. Применение конфигурации

```bash
fwconsole reload
```

---

## Проверка

- В веб-интерфейсе FreePBX: **Connectivity → SCCP Manager** (или **Applications**, в зависимости от версии).
- Должны быть пункты: **Server Config**, **System Parameters**, **Phones Manager**.

## Настройка TFTP и DHCP

- **TFTP:** сервер (например, `tftpd-hpa`) должен отдавать файлы из `/tftpboot`.
- **DHCP:** опция 66 (TFTP server) и при необходимости 150 (Cisco) для указания телефонам адреса TFTP и имени конфига.

Подробнее: [chan-sccp wiki — TFTP](https://github.com/chan-sccp/chan-sccp/wiki/setup-tftp-service), [DHCP](https://github.com/chan-sccp/chan-sccp/wiki/setup-dhcp-service).

## Обновление модуля

Через CLI (рекомендуется, если через Module Admin обновление не срабатывает):

```bash
cd /tmp
curl -sL -o sccp_manager.zip "https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip"
unzip -q -o sccp_manager.zip
rm -rf /var/www/html/admin/modules/sccp_manager
cp -a sccp_manager-develop /var/www/html/admin/modules/sccp_manager
chown -R asterisk:asterisk /var/www/html/admin/modules/sccp_manager
fwconsole ma install sccp_manager --force
fwconsole reload
```

## Удаление

В FreePBX: **Module Admin** → найдите **SCCP Manager** → **Uninstall**.

Или вручную:
```bash
fwconsole ma uninstall sccp_manager
rm -rf /var/www/html/admin/modules/sccp_manager
fwconsole reload
```
