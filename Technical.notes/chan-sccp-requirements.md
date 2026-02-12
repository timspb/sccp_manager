# Требования sccp_manager к драйверу chan_sccp

Модуль **sccp_manager** ожидает, что установленный в Asterisk драйвер **chan_sccp** реализует следующий контракт. Репозиторий драйвера: [chan-sccp/chan-sccp](https://github.com/chan-sccp/chan-sccp), форк: [timspb/chan-sccp](https://github.com/timspb/chan-sccp).

## 1. Версия

- **chan-sccp 4.3.4** или новее (рекомендуется **4.3.5+**).
- Для полной поддержки AMI-функций модуля желателен **RevisionNum >= 11063** (новый формат метаданных).

## 2. AMI (Asterisk Manager Interface)

Драйвер должен обрабатывать и отвечать на следующие AMI Action:

| Action | Назначение в sccp_manager |
|--------|---------------------------|
| **SCCPConfigMetaData** [segment] | Версия и метаданные. Должен возвращать в ответе/событиях: `Version`, опционально `RevisionNum`, `RevisionHash`, `ConfigureEnabled`, для segment `general` — `Options` (подсказки для GUI). |
| **Command** с командой `realtime mysql status` | Проверка realtime-подключения. Вывод разбирается на строки; строка с `connected` даёт статус OK. |
| **Reload** с модулем `chan_sccp` | Перезагрузка конфигурации драйвера после сохранения настроек. |
| **SCCPShowDevices** | Список устройств (для GUI). Ожидается событие завершения `SCCPShowDevicesComplete`. |
| **SCCPShowDevice** \<name\> | Информация по одному устройству. Ожидается `SCCPShowDeviceComplete`. |
| **SCCPShowSoftkeySets** | Список наборов софт-клавиш. Ожидается `SCCPShowSoftKeySetsComplete`. |
| **SCCPDeviceRestart** / **SCCPTokenAck** | Сброс/перезагрузка устройства по имени (Reset Device / Reset Token в GUI). |

Формат ответов/событий должен соответствовать классам в `sccp_manager` (см. `amInterfaceClasses/Message.class.php`, `Response.class.php`, `Event.class.php`).

## 3. Realtime (MySQL/MariaDB)

- Драйвер читает конфигурацию из **realtime** (таблицы, создаваемые/обновляемые установщиком sccp_manager).
- Ожидаемые таблицы/представления (схема задаётся в `install.php` и `module.xml`): **sccpdevice**, **sccpline**, **sccpsettings**, **sccpbuttonconfig**, **sccpdevmodel**, **sccpuser**; представление **sccpdeviceconfig** и т.п.
- Команда `realtime mysql status` должна отражать подключение к той же БД, которую использует FreePBX/sccp_manager.

## 4. Сборка драйвера

Рекомендуемые флаги configure (для полной совместимости с возможностями модуля):

```bash
./configure --enable-conference --enable-advanced-functions --enable-distributed-devicestate --enable-video
```

## 5. Чек-лист для проверки форка timspb/chan-sccp

- [ ] В исходниках драйвера реализованы все AMI Action из таблицы выше (поиск по именам в C-коде).
- [ ] Ответ на **SCCPConfigMetaData** содержит `Version` и при необходимости `RevisionNum` (≥ 11063 для новых сборок).
- [ ] Ответ на **Command** `realtime mysql status` выводит строки с информацией о realtime (в т.ч. «connected»).
- [ ] Реализованы события завершения: `SCCPShowDevicesComplete`, `SCCPShowDeviceComplete`, `SCCPShowSoftKeySetsComplete`.
- [ ] Схема realtime (таблицы sccpdevice, sccpline, …) совместима с той, что создаёт sccp_manager (сравнить с `module.xml` и `install.php`).
- [ ] Сборка с указанными флагами configure проходит без ошибок.
- [ ] Совместимость с Asterisk 21 (и при необходимости 22/23) проверена (API AMI, загрузка модуля).

---

Чтобы провести полный разбор кода **timspb/chan-sccp** прямо в этом проекте, можно клонировать репозиторий рядом с `sccp_manager`:

```bash
cd e:\
git clone https://github.com/timspb/chan-sccp.git
```

После этого можно будет просмотреть исходники драйвера (например, поиск по AMI-именам в `chan-sccp/src/`).
