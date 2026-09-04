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
cd ~/moodle && npm install && npx grunt amd --root=blocks/catquiz_feedbackwizard
git -C blocks/catquiz_feedbackwizard diff --exit-code amd/build/

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

## 11. Engine-Plugins sind Pflicht, nicht optional

`local_catquizlab` erkennt `local_catquiz` und `mod_adaptivequiz` zur Laufzeit
und schaltet ohne sie sauber ab. Dieses Plugin tut das nicht. In `version.php`
stehen:

| Komponente | Mindestversion | Rolle |
|---|---|---|
| `mod_adaptivequiz` | 2026081900 | Trägeraktivität, definiert den Subplugin-Typ `adaptivequizcatmodel` |
| `adaptivequizcatmodel_catquiz` | 2026081900 | Brücke zwischen Aktivität und Engine |
| `local_catquiz` | 2026083025 | CAT-Engine, Skalen, Kontexte, Testumgebungen |

`local_catquiz` zieht zusätzlich `local_wunderbyte_table` nach.

Der Block schreibt seit 0.4.2 ausschließlich über
`\local_catquiz\testenvironment` (siehe `classes/local/adapter/`). Ein
PHPUnit-Lauf ohne installierte Engine überspringt deshalb genau die Tests, die
den Schreibpfad prüfen — `local_catquiz_adapter_test` markiert sich in dem Fall
selbst als skipped. Grün ohne Engine bedeutet also weniger, als es aussieht.

Beim Aktualisieren dieser Plugins gilt: Engine-Interna nie raten, sondern im
Quellcode nachsehen. Die Testumgebungs-API steht in
`local_catquiz/classes/testenvironment.php`, der Einstieg der Aktivität in
`local_catquiz/classes/catquiz_handler.php`, die verfügbaren Modelle unter
`local_catquiz/catmodel/`.

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
| Nur ein `catquiz`-Verzeichnis im Engine-Ordner | voller Komponentenname als Verzeichnisname nötig |
| `composer: command not found` | Composer ist nicht vorinstalliert; siehe Abschnitt 7 |
| Composer meldet „plugins have been disabled for safety" | `COMPOSER_ALLOW_SUPERUSER=1` fehlt |
| `npx grunt` findet keine Tasks | `npm install` im Moodle-Wurzelverzeichnis vergessen, nicht im Plugin |
| Behat meldet „Connection refused" auf 8001 | PHP-Webserver läuft nicht; `php -S 127.0.0.1:8001 -t ~/moodle` |

## 13. Protokoll des Referenzlaufs

Durchlaufen am 2026-09-04 auf einem frischen Ubuntu-24.04-Container gegen
`block_catquiz_feedbackwizard` 0.4.5 und Moodle 4.5.13+ (Build 20260903),
PHP 8.3.6, PostgreSQL 16.15.

Installierte Komponenten:

| Komponente | Version |
|---|---|
| `block_catquiz_feedbackwizard` | 2026090405 |
| `mod_adaptivequiz` | 2026082705 |
| `adaptivequizcatmodel_catquiz` | 2026082704 |
| `local_catquiz` | 2026083025 |
| `local_wunderbyte_table` | 2026081801 |

Ergebnis der fünf Gates:

| Gate | Ergebnis |
|---|---|
| PHPUnit | 36 Tests, 180 Assertions, alle grün |
| phpcs (Moodle-Standard) | 4 Fehler gefunden, per `phpcbf` behoben, danach sauber |
| PHPDoc (moodlecheck) | 1 Fehler gefunden und behoben, danach sauber |
| AMD-Bundle | Neubau identisch zum eingecheckten Stand |
| Behat | 2 Szenarien, 17 Schritte, alle grün |

Die phpcs-Funde waren dreimal `static function(` ohne Leerzeichen und einmal
eine doppelte Leerzeile; der PHPDoc-Fund war ein fehlender `@param`-Eintrag
für den neuen `$courseid`-Parameter von `catquiz_data::get_test_by_id()`.

Wichtig für die Bewertung des PHPUnit-Laufs: `local_catquiz_adapter_test::
test_save_test_configuration_persists_json` hat sich **nicht** übersprungen,
sondern ist gegen die installierte Engine gelaufen. Der Schreibpfad über
`\local_catquiz\testenvironment` ist damit belegt und nicht nur behauptet.

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
