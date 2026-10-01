# Entwicklungs- und Testumgebung

Diese Anleitung beschreibt eine Umgebung, in der sich
`block_catquiz_feedbackwizard` vollständig verifizieren lässt: PHPUnit, Behat,
phpcs und die PHPDoc-Prüfung.

Sie ist von der Anleitung für `local_catquizlab` abgeleitet, um die
Unterschiede dieses Plugins ergänzt und **auf einem frischen
Ubuntu-24.04-Container vollständig durchlaufen worden**. Der Protokollstand
steht in Abschnitt 14; die dort genannten Abweichungen sind bereits in die
Schritte unten eingearbeitet. Der wichtigste Unterschied steht in
Abschnitt 11: **dieses Plugin ist ohne die Engine-Plugins nicht
installierbar.** `local_catquizlab` erkennt die Engine zur Laufzeit und läuft
auch ohne sie; hier stehen `mod_adaptivequiz`, `adaptivequizcatmodel_catquiz`
und `local_catquiz` als harte Abhängigkeiten in `version.php`, und Moodle
verweigert die Installation, solange eines davon fehlt.

---

## 1. Systempakete

```bash
apt-get update
apt-get install -y --no-install-recommends \
    php-cli php-xml php-mbstring php-curl php-zip php-intl \
    php-pgsql php-gd php-soap php-bcmath \
    postgresql git unzip locales
```

Moodle 4.5 läuft mit PHP 8.1 bis 8.3, Moodle 5.0 und 5.1 mit PHP 8.2 bis 8.4.
Die CI-Matrix dieses Plugins deckt 8.2 bis 8.4 ab; für lokale Arbeit ist 8.3
die bequemste Wahl, weil sie zu allen drei Moodle-Zweigen passt.

Die Extensions sind nicht optional: ohne `pgsql` startet PHPUnit nicht, ohne
`gd`/`soap`/`intl` bricht der Environment-Check der Installation ab.

## 2. PHP-Einstellungen

```bash
PHPINI=$(php -i | grep "Loaded Configuration File" | awk '{print $NF}')
printf "\nmax_input_vars=8000\nmemory_limit=1024M\nmax_execution_time=0\n" >> "$PHPINI"
```

`max_input_vars` muss mindestens 5000 betragen, sonst verweigert
`install_database.php` den Dienst mit einem Environment-Fehler. Der Default von
1000 reicht nicht.

Für dieses Plugin ist der Wert zusätzlich relevant: Schritt 4 des Wizards
erzeugt pro Feedback-Bereich bis zu acht Formularfelder, und `local_catquiz`
legt für jede Skala im Baum weitere an. Bei großen Skalenbäumen kommt man dem
Default sonst tatsächlich nahe.

## 3. Locale

```bash
sed -i 's/^# *en_AU.UTF-8/en_AU.UTF-8/' /etc/locale.gen
locale-gen en_AU.UTF-8
```

Moodles PHPUnit-Initialisierung besteht auf `en_AU.UTF-8` und bricht sonst mit
„Required locale is not installed" ab.

## 4. Datenbank

```bash
service postgresql start
su postgres -c "psql -c \"ALTER USER postgres WITH PASSWORD 'moodle';\""
su postgres -c "createdb moodle"
```

In einem Container ohne systemd überlebt der Dienst keinen Neustart der
Sitzung — vor jedem Testlauf `service postgresql start` aufrufen. Ein
fehlgeschlagener PHPUnit-Bootstrap mit „Connection refused" hat fast immer
diese Ursache und nicht die Konfiguration.

## 5. Moodle, Plugin und Engine

```bash
git clone --depth 1 -b MOODLE_405_STABLE https://github.com/moodle/moodle.git ~/moodle
mkdir -p ~/moodledata ~/moodledata_phpunit ~/moodledata_behat ~/behat_faildumps

git clone -b develop https://github.com/ralferlebach/moodle-block_catquiz_feedbackwizard.git \
    ~/moodle/blocks/catquiz_feedbackwizard

# Engine-Plugins — ohne sie schlägt die Installation des Blocks fehl.
git clone --depth 1 -b v-3.0 https://github.com/ralferlebach/moodle-mod_adaptivequiz.git \
    ~/moodle/mod/adaptivequiz
git clone --depth 1 -b v-3.0 https://github.com/ralferlebach/moodle-adaptivequizcatmodel_catquiz.git \
    ~/moodle/mod/adaptivequiz/catmodel/catquiz
git clone --depth 1 https://github.com/Wunderbyte-GmbH/moodle-local_wunderbyte_table.git \
    ~/moodle/local/wunderbyte_table
git clone --depth 1 https://github.com/ralferlebach/moodle-local_catquiz.git \
    ~/moodle/local/catquiz
```

Das Plugin muss **innerhalb** des Moodle-Baums liegen. Mehrere phpcs-Sniffs
(`moodle.Files.LangFilesOrdering`, `moodle.PHPUnit.TestCaseCovers`) schweigen
bei einer Prüfung außerhalb, und „lokal grün" bedeutet dann nichts.

Beachte den Pfad des Catmodels: `adaptivequizcatmodel_catquiz` ist ein
**Subplugin** von `mod_adaptivequiz` und gehört nach
`mod/adaptivequiz/catmodel/catquiz`, nicht nach `local/`. Der Subplugin-Typ ist
in `mod/adaptivequiz/db/subplugins.json` deklariert.

Für die CI erledigt `.github/scripts/fetch-engine.sh` dasselbe; dort landen
alle vier in einem flachen Verzeichnis mit **vollem Komponentennamen**, weil
`local_catquiz` und `adaptivequizcatmodel_catquiz` sich sonst beide auf
`catquiz` legen und eines davon still verlorengeht.

## 6. config.php

```php
<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'postgres';
$CFG->dbpass    = 'moodle';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = ['dbpersist' => 0, 'dbport' => 5432, 'dbsocket' => '', 'dbcollation' => ''];
$CFG->wwwroot   = 'http://127.0.0.1:8000';
$CFG->dataroot  = '/home/claude/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;

$CFG->phpunit_dataroot = '/home/claude/moodledata_phpunit';
$CFG->phpunit_prefix   = 'phpu_';

$CFG->behat_dataroot      = '/home/claude/moodledata_behat';
$CFG->behat_prefix        = 'bht_';
$CFG->behat_wwwroot       = 'http://127.0.0.1:8001';
$CFG->behat_faildump_path = '/home/claude/behat_faildumps';

$CFG->behat_profiles = [
    'default' => [
        'browser' => 'chrome',
        'wd_host' => 'http://127.0.0.1:4444',
        'capabilities' => [
            'extra_capabilities' => [
                'goog:chromeOptions' => [
                    'binary' => '/tmp/chrome-linux64/chrome',
                    'args'   => [
                        'no-sandbox', 'headless=new', 'disable-dev-shm-usage',
                        'disable-gpu', 'window-size=1366,1000',
                    ],
                ],
            ],
        ],
    ],
];

require_once(__DIR__ . '/lib/setup.php');
```

`behat_wwwroot` **muss** sich von `wwwroot` unterscheiden, sonst verweigert
`admin/tool/behat/cli/init.php` die Konfiguration. Deshalb Port 8001 für Behat
und 8000 für die normale Instanz.

Der Server wird an `127.0.0.1` gebunden, nicht an `localhost`: letzteres kann
auf `::1` auflösen, wo PHPs eingebauter Server nicht lauscht, und der Client
meldet dann HTTP 0.

## 7. Installation

```bash
cd ~/moodle
php admin/cli/install_database.php --agree-license \
    --adminpass='Admin123!' --adminemail=admin@example.com \
    --fullname="CATWizard" --shortname="CATWizard"

# Composer ist auf einem nackten Container nicht vorhanden.
command -v composer || {
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
}

export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-interaction   # PHPUnit, Behat
php admin/tool/phpunit/cli/init.php
```

`COMPOSER_ALLOW_SUPERUSER=1` wird auch für dieses `composer install` gebraucht,
nicht nur für `composer global` in Abschnitt 8. Ohne die Variable deaktiviert
Composer als root seine Plugins und meldet das nur als Warnung — der Lauf
scheint zu gelingen, aber einzelne Pakete werden anders eingerichtet.

`php admin/tool/phpunit/cli/init.php` ist **nach jeder Schemaänderung** des
Plugins erneut aufzurufen, sonst meldet PHPUnit „environment was initialised
for different version". Das gilt auch nach jedem Update der Engine-Plugins,
nicht nur nach Änderungen am Block.

## 8. Prüfwerkzeuge

```bash
export COMPOSER_ALLOW_SUPERUSER=1
composer global require moodlehq/moodle-cs
composer -d ~/.config/composer config --no-plugins \
    allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer -d ~/.config/composer update
export PATH="$HOME/.config/composer/vendor/bin:$PATH"

git clone --depth 1 https://github.com/moodlehq/moodle-local_moodlecheck.git \
    ~/moodle/local/moodlecheck
```

Das phpcs-Composer-Plugin registriert den `moodle`-Standard. Ohne die
`allow-plugins`-Freigabe wird es übersprungen und phpcs meldet „Referenced
sniff 'moodle' does not exist" — der Standard ist dann installiert, aber nicht
angemeldet.

## 8a. Node für den AMD-Build

```bash
curl -sL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs
cd ~/moodle && npm install
```

Das Ubuntu-Paket `nodejs` aus 24.04 ist für Moodles Grunt-Kette zu alt. `npm
install` läuft im **Moodle-Wurzelverzeichnis**, nicht im Plugin — die
Grunt-Konfiguration gehört zu Moodle, nicht zum Plugin.

## 9. Browser für Behat

```bash
V=131.0.6778.85
cd /tmp
curl -sL -o chrome.zip       "https://storage.googleapis.com/chrome-for-testing-public/$V/linux64/chrome-linux64.zip"
curl -sL -o chromedriver.zip "https://storage.googleapis.com/chrome-for-testing-public/$V/linux64/chromedriver-linux64.zip"
unzip -q chrome.zip && unzip -q chromedriver.zip

apt-get install -y --no-install-recommends \
    libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 \
    libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 \
    libpango-1.0-0 libcairo2 libasound2t64
```

Chrome und Chromedriver müssen dieselbe Hauptversion haben. Ist bereits ein
anderes Chrome installiert (etwa unter `/opt/google/chrome`), übernimmt der
Chromedriver dieses und scheitert mit „This version of ChromeDriver only
supports Chrome version 131" — deshalb der `binary`-Eintrag in
`behat_profiles` oben.

Die Ubuntu-Pakete `chromium-browser`/`chromium-chromedriver` sind in 24.04 nur
Snap-Wrapper und in einem Container ohne snapd nutzlos.

**Für dieses Plugin ist Chrome derzeit nicht nötig.** Die beiden Szenarien in
`tests/behat/wizard_block.feature` tragen kein `@javascript`, laufen also über
den BrowserKit-Treiber und brauchen nur den PHP-Webserver auf Port 8001.
Chrome wird erst gebraucht, sobald ein Szenario den Wizard-Modal öffnet — der
läuft über `core_form/modalform` und damit über JavaScript. Wer solche
Szenarien ergänzt, braucht die Chrome-Einrichtung oben.

## 10. Die fünf Gates

```bash
# 1. PHPUnit
service postgresql start
cd ~/moodle && vendor/bin/phpunit --testsuite block_catquiz_feedbackwizard_testsuite --no-coverage

# 2. Coding-Standard
cd ~/moodle/blocks/catquiz_feedbackwizard && phpcs --standard=phpcs.xml --extensions=php .

# 3. PHPDoc
cd ~/moodle && php local/moodlecheck/cli/moodlecheck.php \
    --path=blocks/catquiz_feedbackwizard --format=text

# 4. AMD-Bundle
cd ~/moodle/blocks/catquiz_feedbackwizard/amd && ../../../node_modules/.bin/grunt amd
git -C .. diff --exit-code amd/build/

# 5. Behat
cd ~/moodle
php admin/tool/behat/cli/init.php
(nohup php -S 127.0.0.1:8001 -t ~/moodle >/tmp/webserver.log 2>&1 &)
# Nur wenn @javascript-Szenarien dabei sind:
# (nohup /tmp/chromedriver-linux64/chromedriver --port=4444 >/tmp/chromedriver.log 2>&1 &)
vendor/bin/behat --config ~/moodledata_behat/behatrun/behat/behat.yml \
    --tags @block_catquiz_feedbackwizard
```

Gate 4 hat in `local_catquizlab` keine Entsprechung: dort gibt es kein AMD.
Dieses Plugin liefert `amd/src/main.js` mit eingechecktem Bundle unter
`amd/build/`. Weicht das Bundle von der Quelle ab, meldet die CI das — der
`git diff` oben zeigt dasselbe lokal.

Zwei Details, die je einmal einen roten CI-Lauf gekostet haben:

- **Beide Dateien prüfen, nicht nur das Bundle.** `moodle-plugin-ci` vergleicht
  `*.js`, `*.js.map` und `*.css` per sha1. Ein `git diff` auf das ganze
  `amd/build/`-Verzeichnis deckt das ab; ein Vergleich nur von
  `main.min.js` nicht.
- **Kein abschließender Zeilenumbruch in `amd/build/`.** Rollup schreibt die
  Sourcemap ohne, und ein Editor, der beim Speichern einen anhängt, macht die
  Datei für die CI stale, ohne dass sich eine Zeile Code geändert hat. Die
  mitgelieferte `.editorconfig` nimmt `amd/build/**` deshalb von
  `insert_final_newline` aus.

Der Aufruf oben entspricht dem der CI: `moodle-plugin-ci` führt den
`amd`-Task mit dem Arbeitsverzeichnis `<plugin>/amd` aus, nicht aus dem
Moodle-Wurzelverzeichnis mit `--root`.

## 11. Engine-Plugins sind Pflicht — und es gibt zwei Stacks

`local_catquizlab` erkennt `local_catquiz` und `mod_adaptivequiz` zur Laufzeit
und schaltet ohne sie sauber ab. Dieses Plugin tut das nicht: die drei
Komponenten stehen als harte Abhängigkeiten in `version.php`, und Moodle
verweigert die Installation, solange eine fehlt.

Seit September 2026 gibt es **zwei Engine-Stacks, die sich nicht überlappen**:

| Stack | Zweig | local_catquiz | Moodle |
|---|---|---|---|
| `legacy` | `ALiSe-v-1.2.0-legacy` | 1.2.1 (2026092616) | `supported = [405, 405]` |
| `v5` | `migration-zu-moodle-5.x` bzw. `v-3.0` | 1.3.0 (2026092617) | `supported = [501, 503]` |

Die zugehörigen Versionen:

| Komponente | legacy | v5 |
|---|---|---|
| `mod_adaptivequiz` | 3.0.0 / 2026090604 (`ALiSe-v-1.2.0-legacy`) | 3.0.0-rebase.1 / 2026092700 (`v-3.0`) |
| `adaptivequizcatmodel_catquiz` | 1.0.4 / 2026082704 (`ALiSe-v-1.2.0-legacy`) | 1.3.0 / 2026092700 (`v-3.0`) |
| `local_catquiz` | 1.2.1 / 2026092616 | 1.3.0 / 2026092617 |
| `local_wunderbyte_table` | 3.3.2 / 2026081801 (`main`) | dito |

**Moodle 5.0 wird von keinem der beiden Stacks unterstützt.** Der Block lässt
sich dort nicht installieren, weil schon die Engine nicht installierbar ist.
Die CI-Matrix enthält deshalb kein `MOODLE_500_STABLE`, und
`fetch-engine.sh` bricht für diesen Zweig mit einer Erklärung ab statt einen
Stack zu raten.

Die Mindestversionen in `version.php` sind die jeweils **niedrigeren** der
beiden Stacks, damit beide sie erfüllen.

### Engine holen

```bash
# Moodle 4.5
ENGINE_DIR=~/engine MOODLE_BRANCH=MOODLE_405_STABLE \
    bash blocks/catquiz_feedbackwizard/.github/scripts/fetch-engine.sh

# Moodle 5.1+
ENGINE_DIR=~/engine MOODLE_BRANCH=MOODLE_501_STABLE \
    bash blocks/catquiz_feedbackwizard/.github/scripts/fetch-engine.sh
```

Das Skript leitet den Stack aus `MOODLE_BRANCH` ab; `ENGINE_STACK=legacy|v5`
übersteuert das.

### Submodule nicht vergessen

`local_catquiz` führt `catquizcentralhub/client` und `catquizcentralhub/host`
als **Git-Submodule**, und `db/subplugins.json` deklariert
`catquizcentralhub` als Plugin-Typ. Ein `git clone` ohne
`--recurse-submodules` hinterlässt dort zwei leere Verzeichnisse, die Moodles
Plugin-Manager als Plugins zu laden versucht:

```text
include(.../catquizcentralhub/client/version.php): Failed to open stream
```

Diese Warnung erscheint auf **jedem** Pfad, der Plugins aufzählt — auch
tief in `\core_ai\manager`. Unter `--fail-on-warning` kippt damit der
PHPUnit-Lauf, und die Fehlermeldung zeigt auf die zuletzt aufrufende Stelle
statt auf die Ursache. `fetch-engine.sh` klont deshalb mit
`--recurse-submodules --shallow-submodules` und bricht ab, wenn danach ein
Unterverzeichnis ohne `version.php` übrigbleibt.

Beim Klonen von Hand entsprechend:

```bash
git clone --recurse-submodules -b ALiSe-v-1.2.0-legacy \
    https://github.com/ralferlebach/moodle-local_catquiz.git ~/moodle/local/catquiz
```

### Moodle 5.1 hat ein anderes Verzeichnislayout

Ab Moodle 5.1 liegt die Anwendung unter `public/`, während `admin/cli/` und
`lib/setup.php` im Wurzelverzeichnis bleiben. Für die lokale Einrichtung
heißt das:

| | Moodle 4.5 | Moodle 5.1+ |
|---|---|---|
| Plugin | `~/moodle/blocks/catquiz_feedbackwizard` | `~/moodle/public/blocks/catquiz_feedbackwizard` |
| Engine | `~/moodle/local/catquiz` | `~/moodle/public/local/catquiz` |
| config.php | `~/moodle/config.php` | `~/moodle/config.php` (unverändert) |
| `require_once` darin | `/lib/setup.php` | `/lib/setup.php` (unverändert) |
| Datenbank-Installation | `php admin/cli/install_database.php` | dito |
| PHPUnit-Init | `php admin/tool/phpunit/cli/init.php` | `php public/admin/tool/phpunit/cli/init.php` |
| `vendor/bin/phpunit` | Wurzel | Wurzel (unverändert) |

Die Falle ist, dass einige CLI-Einstiege im Wurzelverzeichnis liegen und
andere nur unter `public/`. `admin/cli/install_database.php` gibt es im
Wurzelverzeichnis, `admin/tool/phpunit/cli/init.php` nicht.

`moodle-plugin-ci` kennt beide Layouts und legt die Plugins selbst richtig ab
— die CI braucht dafür keine Sonderbehandlung.

### Was das für die Tests bedeutet

Der Block schreibt ausschließlich über `\local_catquiz\testenvironment`
(siehe `classes/local/adapter/`). Diese Klasse ist in 1.2.1 und 1.3.0
**bytegleich**, und `local_catquiz_tests` wie `local_catquiz_catscales` haben
in beiden Zweigen dieselben Spalten — der Adapter trägt also über beide
Stacks.

Ein PHPUnit-Lauf ohne installierte Engine überspringt genau die Tests, die den
Schreibpfad prüfen: `local_catquiz_adapter_test` markiert sich dann selbst als
skipped. Grün ohne Engine bedeutet weniger, als es aussieht.

Beim Aktualisieren gilt: Engine-Interna nie raten, sondern im Quellcode
nachsehen — und zwar **in jedem Zweig, den die Matrix fährt**. Die
Testumgebungs-API steht in `local_catquiz/classes/testenvironment.php`, der
Einstieg der Aktivität in `local_catquiz/classes/catquiz_handler.php`.

## 12. Wiederkehrende Stolpersteine

| Symptom | Ursache |
|---|---|
| „Connection refused" beim PHPUnit-Bootstrap | PostgreSQL läuft nicht mehr; `service postgresql start` |
| „environment was initialised for different version" | Schemaänderung im Block oder in der Engine; `admin/tool/phpunit/cli/init.php` erneut ausführen |
| „Referenced sniff 'moodle' does not exist" | phpcs-Composer-Plugin nicht freigegeben |
| „behat_wwwroot must be different from wwwroot" | beide Ports identisch |
| „ChromeDriver only supports Chrome version N" | fremdes Chrome im Pfad; `binary` in `behat_profiles` setzen |
| phpcs lokal grün, CI rot | Plugin außerhalb des Moodle-Baums geprüft |
| „max_input_vars must be at least 5000" | PHP-Default 1000 nicht erhöht |
| Block lässt sich nicht installieren, „requires … local_catquiz" | Engine fehlt; siehe Abschnitt 11 |
| `local_catquiz_adapter_test` wird übersprungen | Engine fehlt; der Schreibpfad ist dann ungeprüft |
| KI-Abschnitt in Schritt 4 fehlt trotz aktivem Setting | kein `core_ai`-Provider für `generate_text` konfiguriert |
| PHPUnit lokal grün, CI nur unter PHP 8.4 rot | PHP-Deprecation; lokal mit `--fail-on-warning` gegenprüfen |
| „File is stale and needs to be rebuilt: …main.min.js.map" | Editor hat einen Zeilenumbruch angehängt; `.editorconfig` beachten |
| PHPUnit nur unter Moodle 5.x rot, 4.5 grün | Engine- oder Core-API zwischen den Zweigen unterschiedlich |
| Nur ein `catquiz`-Verzeichnis im Engine-Ordner | voller Komponentenname als Verzeichnisname nötig |
| „include(.../catquizcentralhub/client/version.php): Failed to open stream" | `local_catquiz` ohne `--recurse-submodules` geklont |
| Engine meldet sich als zu neu/zu alt für die Moodle-Version | falscher Stack; 4.5 braucht `legacy`, 5.1+ braucht `v5` |
| „Could not open input file: admin/tool/phpunit/cli/init.php" | Moodle 5.1: der Einstieg liegt unter `public/` |
| Deutsche Strings erscheinen englisch | Kernsprachpaket `de` nicht installiert; siehe unten |
| `composer: command not found` | Composer ist nicht vorinstalliert; siehe Abschnitt 7 |
| Composer meldet „plugins have been disabled for safety" | `COMPOSER_ALLOW_SUPERUSER=1` fehlt |
| `npx grunt` findet keine Tasks | `npm install` im Moodle-Wurzelverzeichnis vergessen, nicht im Plugin |
| Behat meldet „Connection refused" auf 8001 | PHP-Webserver läuft nicht; `php -S 127.0.0.1:8001 -t ~/moodle` |

## 13. Protokoll des Referenzlaufs

Durchlaufen am 2026-09-28 auf einem frischen Ubuntu-24.04-Container gegen
`block_catquiz_feedbackwizard` 0.4.22 und Moodle 4.5.14+ (Build 20260916),
PHP 8.3.6, PostgreSQL 16.15.

Installierte Komponenten:

| Komponente | Version |
|---|---|
| `block_catquiz_feedbackwizard` | 2026092811 |
| `mod_adaptivequiz` | 2026090604 (legacy) |
| `adaptivequizcatmodel_catquiz` | 2026082704 (legacy) |
| `local_catquiz` | 2026092616 (1.2.1, legacy) |
| `catquizcentralhub_client` / `_host` | Submodule von local_catquiz |
| `local_wunderbyte_table` | 2026081801 |

Ergebnis der fünf Gates:

| Gate | Ergebnis |
|---|---|
| PHPUnit | 71 Tests, 268 Assertions, alle grün |
| phpcs (Moodle-Standard) | 4 Fehler gefunden, per `phpcbf` behoben, danach sauber |
| PHPDoc (moodlecheck) | 1 Fehler gefunden und behoben, danach sauber |
| AMD-Bundle | Neubau identisch zum eingecheckten Stand |
| Behat | 2 Szenarien, 17 Schritte, alle grün |

Zusätzlich gegen den **`v5`-Stack** auf beiden Zweigen geprüft, die die
CI-Matrix fährt:

| Moodle | PHPUnit | Ergebnis | Engine |
|---|---|---|---|
| 5.1.7+ (Build 20260928) | 11.5.55 | 71 Tests, 268 Assertions, Exit 0 | `local_catquiz` 1.3.0 / 2026092801 |
| 5.2.3+ (Build 20260928) | 11.5.55 | 71 Tests, 268 Assertions, Exit 0 | `local_catquiz` 1.3.0 / 2026092904 |

Die `v5`-Engine zieht laufend nach; die Versionsnummern oben sind der Stand des
jeweiligen Laufs, nicht eine Festlegung.

Moodle 5.2 verlangt laut `admin/environment.xml` mindestens PHP 8.3. Die
CI-Matrix fährt diesen Zweig mit PHP 8.4, der Referenzlauf oben lief mit 8.3 —
beide sind zulässig, die Kombination 5.2 mit 8.3 ist in der CI also nicht
abgedeckt.

`local_catquiz_adapter_test::test_save_test_configuration_persists_json` ist in
beiden Stacks **gelaufen**, nicht übersprungen. Der Schreibpfad über
`\local_catquiz\testenvironment` ist damit gegen 1.2.1 und 1.3.0 belegt.

Die phpcs-Funde waren dreimal `static function(` ohne Leerzeichen und einmal
eine doppelte Leerzeile; der PHPDoc-Fund war ein fehlender `@param`-Eintrag
für den neuen `$courseid`-Parameter von `catquiz_data::get_test_by_id()`.

Wichtig für die Bewertung des PHPUnit-Laufs: `local_catquiz_adapter_test::
test_save_test_configuration_persists_json` hat sich **nicht** übersprungen,
sondern ist gegen die installierte Engine gelaufen. Der Schreibpfad über
`\local_catquiz\testenvironment` ist damit belegt und nicht nur behauptet.

### Tests müssen die Wirkung messen, nicht die Absicht

Ein Test, der prüft, dass unsere eigene Konfiguration unseren eigenen
Schlüssel enthält, ist wertlos: Er ist für einen erfundenen Schlüssel genauso
grün wie für den richtigen. Genau so überlebte
`feedbackactioncoursetarget_N` mehrere Lieferungen, ohne jemals eine Wirkung
zu haben.

Zwei Testarten halten das jetzt auf:

- **`tests/enrolment_action_test.php`** übergibt die geschriebene
  Konfiguration an den Auswahlcode der Engine und prüft am Ende
  `is_enrolled()` und `groups_is_member()`. Gemessen wird die Einschreibung,
  nicht der Schlüsselname.
- **`tests/engine_contract_test.php`** prüft, dass **jeder** vom Writer
  erzeugte Einstellungsschlüssel im Quellcode der installierten Engine
  vorkommt. Ein erfundener Name fällt sofort auf, mit Nennung des Schlüssels.

Beide sind gegengeprüft: Nach absichtlicher Verfälschung des Writers auf die
alten erfundenen Schlüssel schlagen sie fehl („Failed asserting that false is
true" bzw. mit Auflistung der unbekannten Schlüssel). Ein Test, der nicht
fehlschlagen kann, zählt nicht als Absicherung.

Derselbe Ansatz hat beim Adapter einen Engine-Defekt aufgedeckt: Der
Cache-Purge findet statt (gemessen an zwei Caches, die
`changesinquizsettings` als Invalidierungsereignis führen), die
Kontext-Nachführung bei einem Skalenwechsel dagegen nicht — die Bedingung in
`testenvironment::update_object()` vergleicht einen Wert, den sie selbst
zwanzig Zeilen vorher überschrieben hat. Gemeldet als
[local_catquiz#127](https://github.com/ralferlebach/moodle-local_catquiz/issues/127),
ausformuliert in `docs/design/issue-catquiz-contextid-on-scale-change.md`. Die
Dokumentation des Blocks hatte das Gegenteil behauptet und ist korrigiert; der
Adapter zieht den Kontext seither selbst nach, solange #127 offen ist.

Beide überspringen sich ohne installierte Engine — ein grüner Lauf ohne sie
belegt den Schreibpfad also weiterhin nicht.

### Sechste, optionale Prüfung: der interaktive Durchlauf

Die fünf Gates decken den Wizard-Ablauf nicht ab. Behat prüft ohne
`@javascript` nur, ob der Einstiegspunkt erscheint; der Wizard selbst läuft
über `core_form/modalform` und damit über JavaScript.

`tests/e2e/` enthält zwei Playwright-Durchläufe: `wizard.js` fährt die sechs
Schritte durch, `upload.js` deckt zusätzlich die beiden Dateipfade über den
echten Moodle-Filepicker ab (Musterimport in Schritt 2, Feedbackimport in
Schritt 4) sowie den Download über `export.php` samt Roundtrip: Die exportierte
Datei wird direkt wieder importiert. Es läuft nicht in der CI und ist kein Gate, hat aber zwei Fehler
gefunden, die alle fünf Gates überlebt hatten. Details in
`tests/e2e/README.md`.

### Deutsche Strings prüfen

`lang/de/` allein genügt nicht: Moodle bindet den Plugin-Overlay nur ein, wenn
auch das **Kernsprachpaket** installiert ist. Ohne es liefert
`get_language_dependencies('de')` ein leeres Array, und `get_string()` fällt
kommentarlos auf Englisch zurück — das sieht wie eine fehlende Übersetzung aus,
ist aber eine fehlende Voraussetzung.

```bash
php -r 'define("CLI_SCRIPT",true); require("config.php");
require_once($CFG->libdir."/adminlib.php");
(new \tool_langimport\controller())->install_languagepacks("de");'
```

Danach `purge_all_caches()`. `tests/lang_packs_test.php` ist davon unabhängig:
es liest beide Dateien direkt und prüft Schlüsselmenge und Platzhalter, läuft
also auch ohne installiertes Kernsprachpaket.

### Grenzen dieses Laufs

Der Referenzcontainer fährt PHP 8.3 und damit unter Moodle 4.5 die PHPUnit-9-
Reihe. Die CI-Matrix deckt zusätzlich PHP 8.4 und, über Moodle 5.0/5.1,
PHPUnit 11 ab. Beides meldet Dinge, die hier nicht auffallen können:

- **PHP-Deprecations** brechen den CI-Job, weil `moodle-plugin-ci phpunit`
  mit `--fail-on-warning` läuft. Lokal deshalb ebenfalls mit diesem Schalter
  prüfen — der Exit-Code ist der Unterschied, nicht die Testausgabe.
- **PHPUnit-Deprecations** (Metadaten in Docblocks statt Attributen) meldet
  PHPUnit 11 derzeit 18-fach, ohne den Job zu kippen. Auf Attribute umstellen
  lässt sich das nicht, solange Moodle 4.5 mit PHPUnit 9.6 unterstützt wird;
  der Punkt wird erst relevant, wenn 4.5 aus der Matrix fällt.

## 14. Verhältnis zur CI

Die beiden Workflows unter `.github/workflows/` bilden dieselben Gates ab:

- `moodle-ci.yml` — alle Branches außer `main`, also `develop` und
  Feature-Branches. Enthält zusätzlich die rein informativen Prüfungen
  (phpmd, grunt, mustache).
- `moodle-release.yml` — nur `main`, liefert den Status-Check
  „CI complete (release)" für den Branch-Schutz. Volle PHPUnit- und
  Behat-Matrix, ohne die informativen Linter.

Anders als bei `local_catquizlab` holt **jeder** Job, der Moodle installiert,
vorher die Engine über `.github/scripts/fetch-engine.sh` — auch der
Struktur-Lint. Engine-frei bleibt nur `lint-php`, der ohne Moodle-Installation
auskommt und deshalb nie an einem kaputten Engine-Checkout scheitern kann.

Es gibt keinen `worker-check`-Job: dieses Plugin hat kein Node-Verzeichnis.
