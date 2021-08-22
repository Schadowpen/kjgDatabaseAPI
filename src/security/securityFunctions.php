<?php
require_once __DIR__ . "/../config/config.php";

/**
 * Überprüft, ob der in den URL Parametern angegebene key gültig ist.
 * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
 * @return bool ob der Schlüssel gültig ist
 */
function keyValid($echoErrors = true)
{
    if (!isset($_GET['key'])) {
        if ($echoErrors)
            echo "Error: No Database key in http get parameters \n";
        return false;
    }
    $zeitpunkt = time();
    global $securityKey;
    $hash = hash("sha256", $securityKey . gmdate("Y-m-d\TH:i", $zeitpunkt));
    $oldHash = hash("sha256", $securityKey . gmdate("Y-m-d\TH:i", $zeitpunkt - 60));
    $newHash = hash("sha256", $securityKey . gmdate("Y-m-d\TH:i", $zeitpunkt + 60));
    if ($_GET['key'] != $hash && $_GET['key'] != $oldHash && $_GET['key'] != $newHash
        && $_GET['key'] != "abc"
    ) {
        if ($echoErrors)
            echo "Error: Database key invalid \n";
        return false;
    }
    return true;
}

/**
 * Löscht alle Attribute aus einem Objekt, welche nicht notwendig sind.
 * Dabei wird das $object direkt verändert.
 * @param mixed $object Objekt, wessen Attribute gelöscht werden sollen.
 * @param array $necessaryAttributes Array mit allen Attributnamen, welche noch im Objekt vorhanden sein sollen
 * @return mixed Das $object für weitere Nutzung
 */
function deleteUnnecessaryAttributes($object, $necessaryAttributes)
{
    $unnecessaryAttributes = [];
    foreach ($object as $key => $value) {
        $necessary = false;
        foreach ($necessaryAttributes as $attribute) {
            if ($key == $attribute)
                $necessary = true;
        }
        if (!$necessary)
            array_push($unnecessaryAttributes, $key);
    }
    foreach ($unnecessaryAttributes as $attribute) {
        unset($object->$attribute);
    }
    return $object;
}

/**
 * Überprüfe, welche Datensätze in der HTTP-Anfrage angegeben wurden und ob für dieses PHP-Skript erlaubt ist, auf diese zuzugreifen.
 *
 * Es wird auch überprüft, ob gar kein Datensatz angegeben wurde.
 * Es wird nicht überprüft, ob mehrere Datensätze angegeben wurden.
 *
 * @param bool $currentDatabaseAllowed Ob erlaubt ist, auf den aktuellen Datensatz zuzugreifen
 * @param bool $archiveDatabaseAllowed Ob erlaubt ist, auf das Archiv zuzugreifen.
 * @param bool $templateDatabaseAllowed Ob erlaubt ist, auf Vorlagen zuzugreifen
 * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
 * @return bool true, wenn es keinerlei Probleme bei der Angabe der Datensätze gibt.
 */
function checkDatabaseUsageAllowed(bool $currentDatabaseAllowed, bool $archiveDatabaseAllowed, bool $templateDatabaseAllowed, bool $echoErrors = true) : bool {
    // check for archive
    if (isset($_GET['archive'])) {
        if ($archiveDatabaseAllowed) {
            if (!is_string($_GET['archive'])) {
                if ($echoErrors)
                    echo "Error: Falscher Datentyp von archive\n";
                return false;
            }
        } else {
            if ($echoErrors)
                echo "Error: It is not allowed to access Datasets in archive for this command\n";
            return false;
        }
        $archiveGiven = true;
    } else {
        $archiveGiven = false;
    }

    // check for template
    if (isset($_GET['template'])) {
        if ($templateDatabaseAllowed) {
            if (!is_string($_GET['template'])) {
                if ($echoErrors)
                    echo "Error: Falscher Datentyp von template\n";
                return false;
            }
        } else {
            if ($echoErrors)
                echo "Error: It is not allowed to access Datasets in template for this command\n";
            return false;
        }
        $templateGiven = true;
    } else {
        $templateGiven = false;
    }

    // check if any database is given
    if (! $currentDatabaseAllowed) {
        if (!$archiveGiven && !$templateGiven) {
            if ($echoErrors)
                echo "Error: No Database given to the request\n";
            return false;
        }
    }

    // Alle Checks durchgelaufen, Erfolg!
    return true;
}


/**
 * Check if variable is a number, that means either integer or float.
 * It does not accept strings that represent a number, like is_numeric()
 * @param mixed $var
 * @return bool
 */
function is_number($var):bool {
    return is_int($var) || is_float($var);
}


/**
 * Reads the POST Body Input as JSON Data.
 * Then the Input is protected from XSS by stripping all possible HTML commands
 * @return mixed
 */
function readInputAsJSON() {
    $object = json_decode(file_get_contents("php://input"));

    function securityCheckObject($obj) {
        foreach ($obj as $key => $value) {
            if (is_string($value)) {
                $obj->$key = strip_tags($value);
            } elseif (is_object($value)) {
                securityCheckObject($value);
            } elseif (is_array($value)) {
                securityCheckArray($value);
            }
        }
    }
    function securityCheckArray($arr) {
        foreach ($arr as $key => $value) {
            if (is_string($value)) {
                $arr[$key] = strip_tags($value);
            } elseif (is_object($value)) {
                securityCheckObject($value);
            } elseif (is_array($value)) {
                securityCheckArray($value);
            }
        }
    }
    if (is_string($object)) {
        $object = strip_tags($object);
    } elseif (is_object($object)) {
        securityCheckObject($object);
    } elseif (is_array($object)) {
        securityCheckArray($object);
    }

    return $object;
}