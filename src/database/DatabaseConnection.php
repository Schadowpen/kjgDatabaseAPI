<?php

namespace database;

require_once __DIR__ . "/../config/config.php";

/**
 * Eine Verbindung zu einem einzelnen Datensatz für ein einzelnes Projekt
 * @package database
 */
class DatabaseConnection
{
    /**
     * Ordner, in welchem sich der Datensatz befindet
     * @var string
     */
    private $databaseFolder;
    /**
     * Datei, über welche readlock und writelock auf den Datensatz geregelt werden können.
     * null, wenn die Datei nicht geöffnet ist.
     * @see fopen()
     * @var resource
     */
    private $lockFile = null;
    /**
     * In welchem Status die Datenbankverbindung sich befindet bzw. welche Rechte die Verbindung hat.
     * @see LOCK_SH Readlock auf dem Datensatz, nur Leserechte
     * @see LOCK_EX Writelock auf dem Datensatz, Lese- und Schreibrechte
     * @see LOCK_UN kein Lock auf dem Datensatz, keine Rechte
     * @var int
     */
    private $lockStatus = LOCK_UN;
    /**
     * Zugehörige Übersicht über die Datenbank. Wird angefragt, um zu überprüfen, ob die Datenbank gesperrt werden darf
     * @var DatabaseOverview
     */
    private $databaseOverview = null;

    /**
     * Liefert den Ordner, in welchem sich der Datensatz befindet.
     * Für Zugriffe auf die Datenbank bitte die anderen Funktionen verwenden
     * @return string
     */
    public function getDatabaseFolder(): string
    {
        return $this->databaseFolder;
    }

    /**
     * Liefert den Status, in welchem sich die Datenbankverbindung befindet,
     * also ob Readlock, Writelock oder kein Lock
     * @return int Statuscode von LOCK_SH, LOCK_EX oder LOCK_UN
     * @see LOCK_SH
     * @see LOCK_EX
     * @see LOCK_UN
     */
    public function getLockStatus(): int
    {
        return $this->lockStatus;
    }

    /**
     * Returns a string representation of lock Status
     * @param int $lock
     * @return string
     */
    public static function lockToString(int $lock): string
    {
        if ($lock == LOCK_UN)
            return "unlock";
        if ($lock == LOCK_SH)
            return "readlock";
        if ($lock == LOCK_EX)
            return "writelock";
        return "errorlock";
    }

    /**
     * Erzeugt eine neue DatabaseConnection.
     * Wenn die DatabaseSubFolder nicht angegeben wird, wird angenommen, dass es der Datensatz unter 'current/' sein soll.
     * @param $databaseOverview DatabaseOverview Überblick über alle Datenbanken. Verwaltet, welche Datenbanken gelockt werden dürfen
     * @param $databaseSubFolder string Unterverzeichnis, in welchem alle Dateien diesers Datensatzes liegen. Ordner sollen mit / getrennt werden.
     */
    public function __construct(DatabaseOverview $databaseOverview, string $databaseSubFolder = "current/")
    {
        global $databaseRootFolder;
        if ($databaseSubFolder[0] === '/')
            $databaseSubFolder = substr($databaseSubFolder, 1);
        if ($databaseSubFolder[strlen($databaseSubFolder) - 1] !== '/')
            $databaseSubFolder = $databaseSubFolder . "/";
        $this->databaseFolder = $databaseRootFolder . $databaseSubFolder;

        $this->databaseOverview = $databaseOverview;
    }

    /**
     * Blockt den Datensatz. Ob readlock oder writelock muss in $LOCK angegeben werden.
     * @param $LOCK int entweder LOCK_SH oder LOCK_EX
     * @param $echoErrors bool Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen
     * @return bool Ob der Datensatz geblockt werden konnte
     */
    private function lockDatabase($LOCK, $echoErrors)
    {
        // check if not already locked
        if ($this->lockFile != null) {
            if ($this->lockStatus == $LOCK)
                return true;
            else {
                if ($echoErrors)
                    echo "Error: tried to " . self::lockToString($LOCK) . " database which is already " . self::lockToString($this->lockStatus) . "\n";
                return false;
            }
        }

        // Check if locking is forbidden
        if (! $this->databaseOverview->isLockAllowed($this)) {
            if ($echoErrors)
                echo "Error: due to deadlock protection, locking " . $this->databaseFolder . " is forbidden\n";
            return false;
        }
        // get File
        $this->lockFile = @fopen($this->databaseFolder . "lockDatabase.txt", "r+");
        if ($this->lockFile == null) {
            if ($echoErrors)
                echo "Error: unable to open " . $this->databaseFolder . "lockDatabase.txt\n";
            return false;
        }
        // lock File
        $locked = flock($this->lockFile, $LOCK);
        if ($locked) {
            $this->lockStatus = $LOCK;
            return true;
        } else {
            fclose($this->lockFile);
            $this->lockFile = null;
            $this->lockStatus = LOCK_UN;
            if ($echoErrors)
                echo "Error: unable to " . $LOCK . " database!\n";
            return false;
        }
    }

    /**
     * Setzt einen Readlock auf den Datensatz
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool Ob der Readlock gesetzt werden konnte
     * @see unlockDatabase()
     */
    public function readlockDatabase($echoErrors = true)
    {
        return $this->lockDatabase(LOCK_SH, $echoErrors);
    }

    /**
     * Setzt einen Writelock auf den Datensatz
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool Ob der Writelock gesetzt werden konnte
     * @see unlockDatabase()
     */
    public function writelockDatabase($echoErrors = true)
    {
        return $this->lockDatabase(LOCK_EX, $echoErrors);
    }

    /**
     * Entfernt jeglichen Read- oder Writelock auf den Datensatz.
     * Rufe diese Funktion auf, am besten nachdem du die Datenbank nicht mehr brauchst, um unnötige Blockaden zu vermeiden.
     *
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool Ob der Lock aufgehoben werden konnte
     * @see readlockDatabase()
     * @see writelockDatabase()
     */
    public function unlockDatabase($echoErrors = true)
    {
        if ($this->lockFile != null) {
            flock($this->lockFile, LOCK_UN);
            fclose($this->lockFile);
            $this->lockFile = null;
            $this->lockStatus = LOCK_UN;
            $this->databaseOverview->notifyDatabaseUnlock();
            return true;
        } else {
            if ($echoErrors)
                echo "Error: unable to unlock Database\n";
            return false;
        }
    }


    /**
     * Liefert den Inhalt einer Datenbankdatei als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param string $databaseFile Datei, welche ausgelesen werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    protected function getDatabaseString($databaseFile, $echoErrors)
    {
        if ($this->lockStatus != LOCK_SH && $this->lockStatus != LOCK_EX) {
            if ($echoErrors)
                echo "Error: Tried to access database without readlock or writelock\n";
            return false;
        }
        return @file_get_contents($this->databaseFolder . $databaseFile);
    }

    /**
     * Liefert den Inhalt einer JSON-Datenbankdatei als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * Die Funktion geht davon aus, dass der Inhalt tatsächlich als reines JSON vorliegt.
     * @param string $databaseFile Datei, welche ausgelesen werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen
     * @return null|mixed null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    protected function getDatabaseJson($databaseFile, $echoErrors)
    {
        $fileContent = $this->getDatabaseString($databaseFile, $echoErrors);
        if ($fileContent === false)
            return null;
        else
            return json_decode($fileContent);
    }

    /**
     * Speichert einen String in eine Datenbankdatei.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string String, welcher in die Datei geschrieben werden soll
     * @param string $databaseFile Datei, in welche geschrieben werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    protected function setDatabaseString($string, $databaseFile, $echoErrors)
    {
        if ($this->lockStatus != LOCK_EX) {
            if ($echoErrors)
                echo "Error: Tried to write in database without writelock\n";
            return false;
        }
        return file_put_contents($this->databaseFolder . $databaseFile, $string);
    }

    /**
     * Speichert ein Objekt in JSON Kodiert in eine Datenbankdatei.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param mixed $object Objekt, welches in JSON Kodiert und in die Datei geschrieben werden soll.
     * @param string $databaseFile Datei, in welche geschrieben werden soll
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    protected function setDatabaseJson($object, $databaseFile, $echoErrors)
    {
        return $this->setDatabaseString(json_encode($object), $databaseFile, $echoErrors);
    }


    /**
     * Liefert den Inhalt der Datenbanktabelle "bereiche" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getBereicheString($echoErrors = true)
    {
        return $this->getDatabaseString("bereiche.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "bereiche" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public function getBereicheJson($echoErrors = true)
    {
        return $this->getDatabaseJson("bereiche.json", $echoErrors);
    }
    /**
     * Liefert den Inhalt der Datenbanktabelle "eingaenge" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getEingaengeString($echoErrors = true)
    {
        return $this->getDatabaseString("eingaenge.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "eingaenge" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public function getEingaengeJson($echoErrors = true)
    {
        return $this->getDatabaseJson("eingaenge.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "kartenConfig" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getKartenConfigString($echoErrors = true)
    {
        return $this->getDatabaseString("kartenConfig.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "kartenConfig" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Objekt
     */
    public function getKartenConfigJson($echoErrors = true)
    {
        return $this->getDatabaseJson("kartenConfig.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Vorlage für Theaterkarten als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getKartenVorlageString($echoErrors = true)
    {
        return $this->getDatabaseString("kartenVorlage.pdf", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "plaetze" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getPlaetzeString($echoErrors = true)
    {
        return $this->getDatabaseString("plaetze.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "plaetze" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public function getPlaetzeJson($echoErrors = true)
    {
        return $this->getDatabaseJson("plaetze.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "platzStatusse" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getPlatzStatusseString($echoErrors = true)
    {
        return $this->getDatabaseString("platzStatusse.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "platzStatusse" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public function getPlatzStatusseJson($echoErrors = true)
    {
        return $this->getDatabaseJson("platzStatusse.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "veranstaltung" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getVeranstaltungString($echoErrors = true)
    {
        return $this->getDatabaseString("veranstaltung.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "veranstaltung" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return object|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Objekt
     */
    public function getVeranstaltungJson($echoErrors = true)
    {
        return $this->getDatabaseJson("veranstaltung.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "vorgaenge" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getVorganengeString($echoErrors = true)
    {
        return $this->getDatabaseString("vorgaenge.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "vorgaenge" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public function getVorgaengeJson($echoErrors = true)
    {
        return $this->getDatabaseJson("vorgaenge.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "vorstellungen" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getVorstellungenString($echoErrors = true)
    {
        return $this->getDatabaseString("vorstellungen.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "vorstellungen" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public function getVorstellungenJson($echoErrors = true)
    {
        return $this->getDatabaseJson("vorstellungen.json", $echoErrors);
    }


    /**
     * Speichert einen String, der die "bereiche" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setBereicheString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "bereiche.json", $echoErrors);
    }

    /**
     * Speichert die "bereiche" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param array $object Array mit allen zu speichernden Datenbankobjekten
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setBereicheJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "bereiche.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die "eingaenge" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setEingaengeString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "eingaenge.json", $echoErrors);
    }

    /**
     * Speichert die "eingaenge" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param array $object Array mit allen zu speichernden Datenbankobjekten
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setEingaengeJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "eingaenge.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die "kartenConfig" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setKartenConfigString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "kartenConfig.json", $echoErrors);
    }

    /**
     * Speichert die "kartenConfig" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param object $object Objekt mit der zu speichernden Konfiguration
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setKartenConfigJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "kartenConfig.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die Vorlage für Theaterkarten als PDF beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültigen PDF-Inhalt darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit PDF-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setKartenVorlageString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "kartenVorlage.pdf", $echoErrors);
    }

    /**
     * Speichert einen String, der die "plaetze" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setPlaetzeString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "plaetze.json", $echoErrors);
    }

    /**
     * Speichert die "plaetze" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param array $object Array mit allen zu speichernden Datenbankobjekten
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setPlaetzeJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "plaetze.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die "platzStatusse" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setPlatzStatusseString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "platzStatusse.json", $echoErrors);
    }

    /**
     * Speichert die "platzStatusse" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param array $object Array mit allen zu speichernden Datenbankobjekten
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setPlatzStatusseJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "platzStatusse.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die "veranstaltung" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setVeranstaltungString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "veranstaltung.json", $echoErrors);
    }

    /**
     * Speichert die "veranstaltung" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param object $object Zu speicherndes Datenbankobjekt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setVeranstaltungJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "veranstaltung.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die "vorgaenge" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setVorgaengeString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "vorgaenge.json", $echoErrors);
    }

    /**
     * Speichert die "vorgaenge" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param array $object Array mit allen zu speichernden Datenbankobjekten
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setVorgaengeJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "vorgaenge.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die "vorstellungen" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setVorstellungenString($string, $echoErrors = true)
    {
        return $this->setDatabaseString($string, "vorstellungen.json", $echoErrors);
    }

    /**
     * Speichert die "vorstellungen" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param array $object Array mit allen zu speichernden Datenbankobjekten
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setVorstellungenJson($object, $echoErrors = true)
    {
        return $this->setDatabaseJson($object, "vorstellungen.json", $echoErrors);
    }
}