<?php


namespace database;


class TemplateDatabaseConnection extends DatabaseConnection
{
    public function getPlatzStatusseJson($echoErrors = true)
    {
        return $this->platzStatusseError($echoErrors);
    }

    public function getPlatzStatusseString($echoErrors = true)
    {
        return $this->platzStatusseError($echoErrors);
    }

    public function setPlatzStatusseJson($object, $echoErrors = true)
    {
        return $this->platzStatusseError($echoErrors);
    }

    public function setPlatzStatusseString($string, $echoErrors = true)
    {
        return $this->platzStatusseError($echoErrors);
    }

    /**
     * Fehlerbehandlung, da platzStatusse nicht n einer TemplateDatabase existieren
     * @param $echoErrors bool Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen
     * @return bool false = Error während Dateizugriff
     */
    private function platzStatusseError($echoErrors)
    {
        if ($echoErrors)
            echo "Error: PlatzStatusse do not exist for template databases!\n";
        return false;
    }

    public function getVorgaengeJson($echoErrors = true)
    {
        return $this->vorgaengeError($echoErrors);
    }

    public function getVorganengeString($echoErrors = true)
    {
        return $this->vorgaengeError($echoErrors);
    }

    public function setVorgaengeJson($object, $echoErrors = true)
    {
        return $this->vorgaengeError($echoErrors);
    }

    public function setVorgaengeString($string, $echoErrors = true)
    {
        return $this->vorgaengeError($echoErrors);
    }

    /**
     * Fehlerbehandlung, da vorgaenge nicht in einer TemplateDatabase existieren
     * @param $echoErrors bool Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen
     * @return bool false = Error während Dateizugriff
     */
    private function vorgaengeError($echoErrors)
    {
        if ($echoErrors)
            echo "Error: Vorgaenge do not exist for template databases!\n";
        return false;
    }


    /**
     * Liefert den Inhalt der Datenbanktabelle "platzGruppen" als assoziatives Array.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return array|null null wenn nicht erfolgreich, ansonsten den JSON-Inhalt als Array von Objekten
     */
    public function getPlatzGruppenJson($echoErrors = true) {
        return $this->getDatabaseJson("platzGruppen.json", $echoErrors);
    }

    /**
     * Liefert den Inhalt der Datenbanktabelle "platzGruppen" als String.
     * Fehler, wenn die DatabaseConnection keinen Read- oder Writelock hat.
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return false|string false, wenn nicht erfolgreich, ansonsten den Inhalt als String
     */
    public function getPlatzGruppenString($echoErrors = true) {
        return $this->getDatabaseString("platzGruppen.json", $echoErrors);
    }

    /**
     * Speichert die "platzGruppen" in der Datenbank.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param array $object Array mit allen zu speichernden Datenbankobjekten
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setPlatzGruppenJson($object, $echoErrors = true) {
        return $this->setDatabaseJson($object, "platzGruppen.json", $echoErrors);
    }

    /**
     * Speichert einen String, der die "platzGruppen" als JSON beinhaltet, in der Datenbank.
     * Es wird nicht überprüft, ob dieser String gültiges JSON darstellt.
     * Fehler, wenn die DatabaseConnection keinen Writelock hat.
     * @param string $string In der Datenbank zu speichernder String mit JSON-Inhalt
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool|int Anzahl der geschriebenen Bytes, oder false bei einem Fehler
     */
    public function setPlatzGruppenString($string, $echoErrors = true) {
        return $this->setDatabaseString($string, "platzGruppen.json", $echoErrors);
    }
}