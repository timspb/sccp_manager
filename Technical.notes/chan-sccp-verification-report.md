# Проверка драйвера chan_sccp (E:\GitHub\chan-sccp)

Проверка выполнена по требованиям из [chan-sccp-requirements.md](chan-sccp-requirements.md). Репозиторий: **E:\GitHub\chan-sccp** (версия в `.version`: **4.3.5**).

---

## 1. Версия

| Требование | Статус |
|------------|--------|
| chan-sccp 4.3.4+ (рекомендуется 4.3.5+) | **Да** — в `.version` указано `4.3.5`. |
| RevisionNum ≥ 11063 (для новых сборок) | Зависит от сборки: в `sccp_config.c` при наличии VCS в configure подставляются `VCS_NUM`, `VCS_SHORT_HASH`; иначе `RevisionNum: 0`. Нужна сборка с актуальным `autorevision`/версионированием. |

---

## 2. AMI Action

| Action | Найдено в коде | Файл / примечание |
|--------|----------------|-------------------|
| **SCCPConfigMetaData** | Да | `sccp_config.c`: обработка, отправка JSON с `Version`, `RevisionHash`, `RevisionNum`, `ConfigureEnabled`, сегменты; событие `SCCPConfigMetaDataComplete`. |
| **Command** `realtime mysql status` | Ядро Asterisk | Это команда Asterisk (realtime), не chan_sccp. Модуль sccp_manager вызывает её через AMI Command; драйверу реализовывать не нужно. |
| **Reload** | Ядро Asterisk | Перезагрузка модуля выполняется ядром Asterisk по запросу AMI Reload. |
| **SCCPShowDevices** | Да | `sccp_cli.c`: регистрация `SCCPShowDevices`, макрос генерирует событие `SCCPShowDevicesComplete`. |
| **SCCPShowDevice** | Да | `sccp_cli.c`: регистрация `SCCPShowDevice`, событие `SCCPShowDeviceComplete`. |
| **SCCPShowSoftkeySets** | Да | `sccp_cli.c`: регистрация `SCCPShowSoftkeySets`, событие **SCCPShowSoftkeySetsComplete** (с маленькой «k»). |
| **SCCPDeviceRestart** | Да | `sccp_management.c`: команда `SCCPDeviceRestart`. |
| **SCCPTokenAck** | Да | `sccp_cli.c`: регистрация `SCCPTokenAck`. |

---

## 3. События завершения (Complete)

- **SCCPShowDevicesComplete**, **SCCPShowDeviceComplete** — формируются макросом `CLI_AMI_ENTRY` в `pbx_impl/ast*.h`: `Event: " AMI_COMMAND "Complete\r\n"`, т.е. имена совпадают с ожидаемыми в sccp_manager.
- **SCCPShowSoftkeySetsComplete** — драйвер отправляет именно это имя (из `AMI_COMMAND "SCCPShowSoftkeySets"`). В sccp_manager в `Response.class.php` задано `eventlistendevent` = **SCCPShowSoftKeySetsComplete** (с большой «K» в «SoftKey»). Возможное несовпадение регистра: **SCCPShowSoftkeySetsComplete** (драйвер) vs **SCCPShowSoftKeySetsComplete** (модуль). Стоит проверить на реальной системе, чувствительно ли сравнение имён событий к регистру (в PHP обычно да). При необходимости — либо поправить драйвер на `SCCPShowSoftKeySetsComplete`, либо в sccp_manager сравнивать без учёта регистра.

---

## 4. Метаданные SCCPConfigMetaData

В `sccp_config.c` (строки 3325–3395) в JSON выводятся:

- **Version** — `SCCP_VERSION`
- **RevisionHash** — `VCS_SHORT_HASH` или `SCCP_REVISION`
- **RevisionNum** — `VCS_NUM` или 0
- **ConfigureEnabled** — массив возможностей (park, pickup, realtime, video, conference, …)
- **Segments** — список сегментов конфига

Для segment `general` метаданные по опциям и подсказкам отдаются в том же файле (ветка «return metadata for option in segment»). Требования sccp_manager по полям **Version**, **RevisionNum**, **RevisionHash**, **ConfigureEnabled** и опциям для GUI выполняются.

---

## 5. Сборка (configure)

В `configure.ac` и `autoconf/extra.m4` есть опции:

- `--enable-conference`
- `--enable-advanced-functions`
- `--enable-distributed-devicestate`
- `--enable-video`

В `.github/workflows/codeql-analysis.yml` и `contrib/llvm-scan-build` используются все четыре флага. Рекомендуемая строка сборки из требований выполнима.

---

## 6. Asterisk 21

В `configure.ac` заданы `MIN_ASTERISK_VERSION=106` и `MAX_ASTERISK_VERSION=113` (Asterisk 13). Поддержка Asterisk 21 зависит от наличия в дереве `pbx_impl` кода для нужной ветки (например, ast121 или общий слой) и от сборки под нужную версию Asterisk. В каталоге `src/pbx_impl/` видны ast106–ast116; для 21 нужно уточнить, какая ветка используется в вашей сборке (часто один общий слой под несколько версий). Отдельной проверки кода под Asterisk 21 в рамках этого просмотра не было.

---

## Итог

| Критерий | Результат |
|----------|-----------|
| Версия 4.3.5 | Выполнено |
| AMI: SCCPConfigMetaData, Show*, DeviceRestart, TokenAck | Выполнено |
| Метаданные: Version, RevisionNum, ConfigureEnabled | Выполнено |
| Complete-события для Show* | Выполнено; возможное расхождение регистра для SoftKeySets — см. выше |
| Command/Reload | Реализуются Asterisk, не драйвером — ок |
| Флаги configure | Выполнено |
| Realtime-схема | Не проверялась (схему создаёт sccp_manager; драйвер только читает таблицы). |
| Asterisk 21 | Требует отдельной проверки под вашу сборку. |

**Исправление внесено в sccp_manager:** ожидаемое имя события завершения изменено на **SCCPShowSoftkeySetsComplete** (как отправляет драйвер); в `_eventObjFromMsg()` добавлено сопоставление `SCCPShowSoftkeySetsComplete` → класс `SCCPShowSoftKeySetsComplete_Event`, чтобы корректно обрабатывать список софт-клавиш.
