<?php


namespace database;

require_once __DIR__ . "/../config/config.php";

/**
 * Ein Überblick über alle Datensätze in der Datenbank.
 * Zudem dafür verantwortlich, dass keine Deadlocks entstehen.
 * Das wird verhindert, indem die Datenbanken immer absteigend nach ihrem Wurzelverzeichnis gelockt werden (absteigend, weil erst der Name in current gelesen werden muss, bevor archiv angesprochen werden kann).
 * Zudem müssen erst alle Datensets gelockt werden, bevor eine unlocked werden darf
 *
 * Stelle bitte Sicher, dass maximal ein Objekt dieser Klasse erstellt wird!
 * @package database
 */
class DatabaseOverview
{
    /**
     * Content of a new created lockDatabase.txt file
     */
    private const lockDatabaseTxtContent = "Diese Datei muss blockiert werden, bevor mit der Datenbank interagiert wird, damit nur ein PHP-Skript gleichzeitig die Datenbank bearbeitet.";

    /**
     * Array of all archived Databases
     * @var string[]
     */
    private $archiveNames;

    /**
     * Array of all template Databases
     * @var string[]
     */
    private $templateNames;

    /**
     * Array mit allen erstellten DatabaseConnections
     * @var DatabaseConnection[]
     */
    private $databaseConnections = [];

    /**
     * Ob das unlocken von Datensätzen begonnen hat. Danach dürfen keine DatabaseConnections mehr gelockt werden
     * @var bool
     */
    private $unlockingStarted = false;


    public function __construct()
    {
        // Speichere alle Archivnamen als nicht-assoziatives Array
        global $databaseRootFolder;
        $folders = scandir($databaseRootFolder . "archive/");
        $this->archiveNames = [];
        if ($folders != false) {
            foreach ($folders as $key => $value) {
                if (strcmp($value, ".") != 0 && strcmp($value, "..") != 0) {
                    array_push($this->archiveNames, $value);
                }
            }
        }

        // Speichere alle Vorlagennamen als nicht-assoziatives Array
        $folders = scandir($databaseRootFolder . "templates/");
        $this->templateNames = [];
        if ($folders != false) {
            foreach ($folders as $key => $value) {
                if (strcmp($value, ".") != 0 && strcmp($value, "..") != 0) {
                    array_push($this->templateNames, $value);
                }
            }
        }
    }

    /**
     * Liefert die Namen aller archivierten Datenbanken im Archiv
     * @return string[]
     */
    public function getArchivedDatabaseNames(): array
    {
        return $this->archiveNames;
    }

    /**
     * Liefert die Namen aller Vorlagen-Datenbanken im Archiv
     * @return string[]
     */
    public function getTemplateDatabaseNames(): array
    {
        return $this->templateNames;
    }

    /**
     * Überprüft, ob die Datenbank mit diesem Namen im Archiv existiert
     * @param string $archiveName
     * @return bool
     */
    public function archiveDatabaseExists(string $archiveName): bool
    {
        return in_array($archiveName, $this->archiveNames);
    }

    /**
     * Überprüft, ob die Datenbank mit diesem Namen in den Vorlagen existiert
     * @param string $templateName
     * @return bool
     */
    public function templateDatabaseExists(string $templateName): bool
    {
        return in_array($templateName, $this->templateNames);
    }

    /**
     * Liefert eine DatabaseConnection zum aktuellen Datensatz.
     * Es wird nicht überprüft, ob bereits eine Datenbank zu diesem Datensatz existiert
     * @return DatabaseConnection
     */
    public function getCurrentDatabaseConnection(): DatabaseConnection
    {
        $dbc = new DatabaseConnection($this);
        array_push($this->databaseConnections, $dbc);
        return $dbc;
    }

    /**
     * Liefert eine DatabaseConnection zu einem Datensatz im Archiv.
     * Existiert dieser Datensatz nicht im Archiv, wird false zurückgegeben
     * @param string $archiveName Name des Datensatzes im Archiv
     * @param $echoErrors bool Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return DatabaseConnection|false die DatabaseConnection oder false, wenn die DatabaseConnection nicht erstellt werden konnte
     */
    public function getArchiveDatabaseConnection(string $archiveName, bool $echoErrors = true)
    {
        if (!$this->archiveDatabaseExists($archiveName)) {
            if ($echoErrors)
                echo "Error: Database \"$archiveName\" not found in archive\n";
            return false;
        }

        $dbc = new DatabaseConnection($this, "archive/" . $archiveName . "/");
        array_push($this->databaseConnections, $dbc);
        return $dbc;
    }

    /**
     * Erstellt einen neuen Datensatz im Archiv und gibt eine DatabaseConnection dazu zurück.
     * Diese DatabaseConnection hat bereits Writelock. Daher gibt es einen Fehler, sollte der Writelock nicht gesetzt werden können.
     *
     * Theoretisch existiert die Möglichkeit, dass zwei parallele Threads gleichzeitig den Datenset erstellen. Das Zeitfenster dafür ist aber eher gering. Zudem kommt es nicht oft vor, dass Datensets im Archiv neu erstellt werden.
     *
     * @param string $archiveName Name des Archivs, das erstellt werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|DatabaseConnection die DatabaseConnection oder false, wenn das Datenset nicht erstellt werden konnte
     */
    public function createArchiveDatabase(string $archiveName, bool $echoErrors = true)
    {
        if ($this->archiveDatabaseExists($archiveName)) {
            if ($echoErrors)
                echo "Error: Database \"$archiveName\" should be created but already exists\n";
            return false;
        }

        $dbc = new DatabaseConnection($this, "archive/" . $archiveName . "/");
        if (!$this->isLockAllowed($dbc)) {
            if ($echoErrors)
                echo "Error: Could not create database due to deadlock protection\n";
            return false;
        }

        $success = mkdir($dbc->getDatabaseFolder());
        if (!$success) {
            if ($echoErrors)
                echo "Error: Could not create dataset: Could not create new folder\n";
            return false;
        }
        file_put_contents($dbc->getDatabaseFolder() . "lockDatabase.txt", self::lockDatabaseTxtContent);
        $success = $dbc->writelockDatabase($echoErrors);
        if (!$success)
            return false;

        return $dbc;
    }

    /**
     * Existiert das Dataset im Archiv noch nicht, wird es erstellt, andernfalls wird es zurückgegeben.
     *
     * @param string $archiveName Name des Archivs, das erstellt werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|DatabaseConnection die DatabaseConnection oder false, wenn das Datenset nicht erstellt / zurückgegeben werden konnte
     * @see createArchiveDatabase
     */
    public function createArchiveDatabaseIfNotExists(string $archiveName, bool $echoErrors = true)
    {
        if ($this->archiveDatabaseExists($archiveName)) {
            return $this->getArchiveDatabaseConnection($archiveName, $echoErrors);
        } else {
            return $this->createArchiveDatabase($archiveName, $echoErrors);
        }
    }

    /**
     * Liefert eine DatabaseConnection zu einem Datensatz in den Vorlagen.
     * Existiert dieser Datensatz nicht in den Vorlagen, wird false zurückgegeben
     * @param string $templateName Name des Datensatzes in den Vorlagen
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|TemplateDatabaseConnection die DatabaseConnection oder false, wenn die DatabaseConnection nicht erstellt werden konnte
     */
    public function getTemplateDatabaseConnection(string $templateName, bool $echoErrors = true)
    {
        if (!$this->templateDatabaseExists($templateName)) {
            if ($echoErrors)
                echo "Error: Database \"$templateName\" not found in templates\n";
            return false;
        }

        $dbc = new TemplateDatabaseConnection($this, "templates/" . $templateName . "/");
        array_push($this->databaseConnections, $dbc);
        return $dbc;
    }

    /**
     * Erstellt einen neuen Datensatz in den Vorlagen und gibt eine DatabaseConnection dazu zurück.
     * Diese DatabaseConnection hat bereits Writelock. Daher gibt es einen Fehler, sollte der Writelock nicht gesetzt werden können.
     *
     * Theoretisch existiert die Möglichkeit, dass zwei parallele Threads gleichzeitig den Datenset erstellen. Das Zeitfenster dafür ist aber eher gering. Zudem kommt es nicht oft vor, dass Datensets in den Vorlagen neu erstellt werden.
     *
     * @param string $templateName Name der Vorlage, die erstellt werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|DatabaseConnection die DatabaseConnection oder false, wenn das Datenset nicht erstellt werden konnte
     */
    public function createTemplateDatabase(string $templateName, bool $echoErrors = true)
    {
        if ($this->templateDatabaseExists($templateName)) {
            if ($echoErrors)
                echo "Error: Database \"$templateName\" should be created but already exists\n";
            return false;
        }

        $dbc = new TemplateDatabaseConnection($this, "templates/" . $templateName . "/");
        if (!$this->isLockAllowed($dbc)) {
            if ($echoErrors)
                echo "Error: Could not create database due to deadlock protection\n";
            return false;
        }

        $success = mkdir($dbc->getDatabaseFolder());
        if (!$success) {
            if ($echoErrors)
                echo "Error: Could not create dataset: Could not create new folder\n";
            return false;
        }
        file_put_contents($dbc->getDatabaseFolder() . "lockDatabase.txt", self::lockDatabaseTxtContent);
        $success = $dbc->writelockDatabase($echoErrors);
        if (!$success)
            return false;

        return $dbc;
    }

    /**
     * Füllt eine Vorlage mit Standard-Daten, damit diese verwendet werden kann.
     *
     * @param TemplateDatabaseConnection $dbc Verbindung zum Datensatz, der mit Standarddaten gefüllt werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool Ob das Befüllen erfolgreich war
     */
    public function fillTemplateDatabase(TemplateDatabaseConnection $dbc, bool $echoErrors = true)
    {
        global $databaseRootFolder;
        $folders = explode("/", $dbc->getDatabaseFolder());
        $veranstaltung = $folders[count($folders) - 2];

        $successes = array();
        $successes[0] = $dbc->setBereicheString("[]", $echoErrors);
        $successes[1] = $dbc->setEingaengeString("[]", $echoErrors);
        $successes[2] = $dbc->setKartenConfigString("{}", $echoErrors);
        $successes[3] = copy($databaseRootFolder . "templateKartenVorlage.pdf", $dbc->getDatabaseFolder() . "kartenVorlage.pdf");
        $successes[4] = $dbc->setPlaetzeString("[]", $echoErrors);
        $successes[5] = $dbc->setPlatzGruppenString("[]", $echoErrors);
        $successes[6] = $dbc->setVeranstaltungJson((object)[
            "veranstaltung" => $veranstaltung,
            "raumBreite" => 10,
            "raumLaenge" => 10,
            "sitzLaenge" => 0.5,
            "sitzBreite" => 0.5,
            "laengenEinheit" => "Meter",
            "kartenPreis" => 5,
            "versandPreis" => 2.50,
            "additionalFieldsForVorgang" => array()
        ], $echoErrors);
        $successes[7] = $dbc->setVorstellungenString("[]", $echoErrors);

        // return overall success
        foreach ($successes as $success) {
            if ($success == false) {
                if ($echoErrors)
                    echo "Error: Could not fill template Dataset\n";
                return false;
            }
        }
        return true;
    }

    /**
     * Gibt alle bereits erstellten DatabaseConnections zurück
     * @return DatabaseConnection[]
     */
    public function getDatabaseConnections(): array
    {
        return $this->databaseConnections;
    }

    /**
     * Gibt an, ob es erlaubt ist, diese DatabaseConnection zu locken (read- oder writelock)
     * @param DatabaseConnection $databaseConnection wofür überprüft werden soll, ob sie gelockt werden darf
     * @return bool
     */
    public function isLockAllowed(DatabaseConnection $databaseConnection): bool
    {
        if ($this->unlockingStarted)
            return false;

        $targetFolder = $databaseConnection->getDatabaseFolder();
        foreach ($this->databaseConnections as $dbc) {
            if (strcmp($dbc->getDatabaseFolder(), $targetFolder) <= 0 &&
                $dbc->getLockStatus() != LOCK_UN)
                return false;
        }
        return true;
    }

    /**
     * Lockt mehrere DatabaseConnections in der Reihenfolge, in der es vorgesehen ist.
     * @param DatabaseLockHandle[] $databaseLockHandles
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool
     */
    public function lockInOrder(array $databaseLockHandles, $echoErrors = true): bool
    {
        usort($databaseLockHandles, function (DatabaseLockHandle $a, DatabaseLockHandle $b) {
            return -strcmp($a->getDatabaseConnection()->getDatabaseFolder(), $b->getDatabaseConnection()->getDatabaseFolder());
        });

        $overallSuccess = true;
        foreach ($databaseLockHandles as $dbLockHandle) {
            $success = $dbLockHandle->lockDatabase($echoErrors);
            if (!$success)
                $overallSuccess = false;
        }
        return $overallSuccess;
    }

    /**
     * Notify that a database unlocked.
     * Then no database can be locked any more
     */
    public function notifyDatabaseUnlock()
    {
        $this->unlockingStarted = true;
    }

    /**
     * Kopiert von einem Datenset in das andere. Die Daten in $to werden dabei überschrieben
     *
     * @param DatabaseConnection $from Von welchem Datenset aus kopiert werden soll
     * @param DatabaseConnection $to In welches Datenset kopiert werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool Ob die Kopie erfolgreich war
     */
    public function copyDatabase(DatabaseConnection $from, DatabaseConnection $to, bool $echoErrors = true): bool
    {
        // Check Database Lock
        if ($from->getLockStatus() != LOCK_SH && $from->getLockStatus() != LOCK_EX) {
            if ($echoErrors)
                echo "Error: Could not copy Dataset: Source has neither readlock nor writelock\n";
            return false;
        }
        if ($to->getLockStatus() != LOCK_EX) {
            if ($echoErrors)
                echo "Error: Could not copy Dataset: Target has no writelock\n";
            return false;
        }

        // copy all database files
        $successes = array();
        $successes[0] = copy($from->getDatabaseFolder() . "bereiche.json", $to->getDatabaseFolder() . "bereiche.json");
        $successes[1] = copy($from->getDatabaseFolder() . "eingaenge.json", $to->getDatabaseFolder() . "eingaenge.json");
        $successes[2] = copy($from->getDatabaseFolder() . "kartenConfig.json", $to->getDatabaseFolder() . "kartenConfig.json");
        $successes[3] = copy($from->getDatabaseFolder() . "kartenVorlage.pdf", $to->getDatabaseFolder() . "kartenVorlage.pdf");
        $successes[4] = copy($from->getDatabaseFolder() . "plaetze.json", $to->getDatabaseFolder() . "plaetze.json");
        $successes[5] = copy($from->getDatabaseFolder() . "veranstaltung.json", $to->getDatabaseFolder() . "veranstaltung.json");
        $successes[6] = copy($from->getDatabaseFolder() . "vorstellungen.json", $to->getDatabaseFolder() . "vorstellungen.json");
        // copy depending on type of database
        if ($from instanceof TemplateDatabaseConnection && $to instanceof TemplateDatabaseConnection) {
            $successes[7] = copy($from->getDatabaseFolder() . "platzGruppen.json", $to->getDatabaseFolder() . "platzGruppen.json");
        } elseif ($from instanceof TemplateDatabaseConnection) {
            $successes[7] = $to->setPlaetzeJson(self::getPlaetzeIncludingPlatzGruppe($from));
            $successes[8] = $to->setPlatzStatusseString("[]");
            $successes[9] = $to->setVorgaengeString("[]");
        } elseif ($to instanceof TemplateDatabaseConnection) {
            $successes[7] = $to->setPlatzGruppenString("[]", $echoErrors);
        } else {
            $successes[7] = copy($from->getDatabaseFolder() . "platzStatusse.json", $to->getDatabaseFolder() . "platzStatusse.json");
            $successes[8] = copy($from->getDatabaseFolder() . "vorgaenge.json", $to->getDatabaseFolder() . "vorgaenge.json");
        }

        // return overall success
        foreach ($successes as $success) {
            if ($success == false) {
                if ($echoErrors)
                    echo "Error: Could not copy Dataset\n";
                return false;
            }
        }
        return true;
    }

    /**
     * Löscht einen Datensatz aus der Datenbank. Dabei wird das Stammverzeichnis des Datensatzes gelöscht.
     * Der Datensatz wird automatisch unlocked, bevor er gelöscht wird.
     * Bei einer Löschung von /current wird das Verzeichnis sowie die Datei lockDatabase.txt nicht gelöscht.
     *
     * @param DatabaseConnection $dbc Welcher Datensatz gelöscht werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool Ob das Löschen erfolgreich war
     */
    public function deleteDatabase(DatabaseConnection $dbc, bool $echoErrors = true): bool
    {
        // Check Database Lock
        if ($dbc->getLockStatus() != LOCK_EX) {
            if ($echoErrors)
                echo "Error: Could not delete Dataset: Target has no writelock\n";
            return false;
        }

        // Delete all files in Dataset
        $successes = array();
        $successes[0] = @unlink($dbc->getDatabaseFolder() . "bereiche.json");
        $successes[1] = @unlink($dbc->getDatabaseFolder() . "eingaenge.json");
        $successes[2] = @unlink($dbc->getDatabaseFolder() . "kartenConfig.json");
        $successes[3] = @unlink($dbc->getDatabaseFolder() . "kartenVorlage.pdf");
        $successes[4] = @unlink($dbc->getDatabaseFolder() . "plaetze.json");
        $successes[5] = @unlink($dbc->getDatabaseFolder() . "veranstaltung.json");
        $successes[6] = @unlink($dbc->getDatabaseFolder() . "vorstellungen.json");
        if ($dbc instanceof TemplateDatabaseConnection) {
            $successes[7] = @unlink($dbc->getDatabaseFolder() . "platzGruppen.json");
        } else {
            $successes[7] = @unlink($dbc->getDatabaseFolder() . "platzStatusse.json");
            $successes[8] = @unlink($dbc->getDatabaseFolder() . "vorgaenge.json");
        }

        foreach ($successes as $success) {
            if ($success == false) {
                if ($echoErrors)
                    echo "Error: Could not delete file in Dataset\n";
                return false;
            }
        }

        // Unlock Database.
        $success = $dbc->unlockDatabase($echoErrors);
        if (!$success)
            return false;

        // Don't delete lockDatabase.txt and folder for /current
        if ($this->endsWith($dbc->getDatabaseFolder(), "/current/"))
            return true;

        // Delete lockDatabase.txt after unlocking. Possibly now Errors are possible because other threads try to access the same Dataset
        $success = unlink($dbc->getDatabaseFolder() . "lockDatabase.txt");
        if (!$success) {
            if ($echoErrors)
                echo "Error: Could not delete lockDatabase.txt while deleting Dataset\n";
            return false;
        }

        // Delete Database folder
        $success = rmdir($dbc->getDatabaseFolder());
        if ($success == false) {
            if ($echoErrors)
                echo "Error: Could not delete Dataset: Could not remove directory\n";
            return false;
        }
        return true;
    }

    function endsWith($haystack, $needle)
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }


    /**
     * Zerteilt eine platzGruppe in die einzelnen Plaetze
     *
     * @param object $platzGruppe Eine PlatzGruppe
     * @return array Alle Plätze, die diese PlatzGruppe beschreibt
     */
    public static function getPlaetzeFromPlatzGruppe($platzGruppe)
    {
        $reihen = array();
        $reiheVorneCharCode = ord($platzGruppe->reiheVorne);
        $reiheHintenCharCode = ord($platzGruppe->reiheHinten);
        $charCodeJ = ord("J");
        if ($reiheVorneCharCode < $reiheHintenCharCode) {
            for ($c = $reiheVorneCharCode; $c <= $reiheHintenCharCode; $c++) {
                if ($c != $charCodeJ)
                    array_push($reihen, chr($c));
            }
        } else {
            for ($c = $reiheVorneCharCode; $c >= $reiheHintenCharCode; $c--) {
                if ($c != $charCodeJ)
                    array_push($reihen, chr($c));
            }
        }
        $gruppenLaenge = (count($reihen) - 1) * $platzGruppe->reiheAbstand;

        $platzSpalten = array();
        if ($platzGruppe->platzLinks < $platzGruppe->platzRechts) {
            for ($p = $platzGruppe->platzLinks; $p <= $platzGruppe->platzRechts; $p++)
                array_push($platzSpalten, $p);
        } else {
            for ($p = $platzGruppe->platzLinks; $p >= $platzGruppe->platzRechts; $p--)
                array_push($platzSpalten, $p);
        }
        $gruppenBreite = (count($platzSpalten) - 1) * $platzGruppe->platzAbstand;

        $plaetze = array();
        for ($i = 0; $i < count($reihen); $i++) {
            for ($k = 0; $k < count($platzSpalten); $k++) {
                $internalX = -$gruppenBreite / 2 + $k * $platzGruppe->platzAbstand;
                $internalY = $gruppenLaenge / 2 - $i * $platzGruppe->reiheAbstand;
                $rotationRad = deg2rad($platzGruppe->rotation);
                array_push($plaetze, (object)[
                    "block" => $platzGruppe->block,
                    "reihe" => $reihen[$i],
                    "platz" => $platzSpalten[$k],
                    "xPos" => cos($rotationRad) * $internalX - sin($rotationRad) * $internalY + $platzGruppe->xPos,
                    "yPos" => sin($rotationRad) * $internalX + cos($rotationRad) * $internalY + $platzGruppe->yPos,
                    "rotation" => $platzGruppe->rotation,
                    "eingang" => @$platzGruppe->eingang,
                ]);
            }
        }
        return $plaetze;
    }

    /**
     * Liefert alle Sitzplätze zu diesem Datensatz, bestehend aus den Einzelplätzen in plaetze und den platzGruppen.
     *
     * @param TemplateDatabaseConnection $dbc Verbindung zum Vorlagen-Datenset, um dort Plaetze und PlatzGruppe zu lesen
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public static function getPlaetzeIncludingPlatzGruppe(TemplateDatabaseConnection $dbc, bool $echoErrors = true)
    {
        $plaetze = $dbc->getPlaetzeJson($echoErrors);
        if ($plaetze === null)
            return null;
        $platzGruppen = $dbc->getPlatzGruppenJson($echoErrors);
        if ($platzGruppen === null)
            return null;

        foreach ($platzGruppen as $platzGruppe) {
            $gruppenPlaetze = self::getPlaetzeFromPlatzGruppe($platzGruppe);
            $plaetze = array_merge($plaetze, $gruppenPlaetze);
        }

        return $plaetze;
    }

    /**
     * Schwärzt persönliche Daten in Vorgaenge, wenn dies gewünscht ist. Allgemeine Daten zum Vorgang bleiben aber erhalten.
     *
     * @param DatabaseConnection $dbc (archivierte) Datenbank, in welcher Daten geschwärzt werden sollen
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool ob das Schwärzen erfolgreich war
     */
    public static function blackData(DatabaseConnection $dbc, bool $echoErrors = true)
    {
        $veranstaltung = $dbc->getVeranstaltungJson();
        if ($veranstaltung === null)
            return false;
        $vorgaenge = $dbc->getVorgaengeJson($echoErrors);
        if ($vorgaenge === null)
            return false;

        $dataBlacked = false;
        for ($i = 0; $i < count($vorgaenge); ++$i) {
            if (@$vorgaenge[$i]->blackDataInArchive != null && $vorgaenge[$i]->blackDataInArchive) {
                $vorgaenge[$i]->vorname = "---";
                $vorgaenge[$i]->nachname = "---";
                $vorgaenge[$i]->email = "---";
                $vorgaenge[$i]->telefon = "---";
                $vorgaenge[$i]->anschrift = "---";
                unset($vorgaenge[$i]->kommentar);
                if (isset($veranstaltung->additionalFieldsForVorgang)) {
                    foreach ($veranstaltung->additionalFieldsForVorgang as $additionalField)
                        unset($vorgaenge[$i]->{$additionalField->fieldName});
                }
                $dataBlacked = true;
            }
        }

        if ($dataBlacked) {
            $writtenBytes = $dbc->setVorgaengeJson($vorgaenge, $echoErrors);
            if ($writtenBytes === false)
                return false;
        }
        return true;
    }
}