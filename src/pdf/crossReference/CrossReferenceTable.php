<?php
//require_once "CrossReferenceTableEntry.php";

namespace pdf\crossReference;

class CrossReferenceTable
{
    /**
     * Array mit allen Einträgen in der Tabelle. Die Arraypositionen sind gleichzeitig die Objektnummern
     * @var CrossReferenceTableEntry[]
     */
    private $references;

    public function __construct()
    {
        $this->references = [];
    }

    /**
     * Fügt einen neuen Eintrag in die CrossReferenceTable hinzu
     * @param $tableEntry CrossReferenceTableEntry
     */
    public function setEntry($tableEntry)
    {
        $this->references[$tableEntry->getObjNumber()] = $tableEntry;
    }

    /**
     * Gibt einen Eintrag aus dieser CrossReferenceTable zurück
     * @param $objNumber int Nummer des zu Erhaltenden Eintrages
     * @return null|CrossReferenceTableEntry
     */
    public function getEntry(int $objNumber)
    {
        return @$this->references[$objNumber];
    }

    /**
     * Überprüft, ob ein Eintrag in dieser CrossReferenceTable vorhanden ist
     * @param int $objNumber Zu überprüfende Nummer
     * @return bool
     */
    public function hasEntry(int $objNumber)
    {
        return @$this->references[$objNumber] != null;
    }

    /**
     * Löscht einen Eintrag aus der CrossReferenceTable
     * @param int $objNumber Nummer des zu löschenden Eintrages
     */
    public function removeEntry(int $objNumber)
    {
        unset($this->references[$objNumber]);
    }

    /**
     * Liefert alle Einträge in der CrossReferenceTable.
     * Die Positionen im Array stimmen mit den Objektnummern überein
     * @return CrossReferenceTableEntry[]
     */
    public function getAll()
    {
        return $this->references;
    }

    /**
     * Sortiert die Einträge in der CrossReferenceTable aufsteigend
     */
    public function sortEntries()
    {
        ksort($this->references);
    }

    /**
     * Transformiert die CrossReferenceTable in einen fertigen String zum Einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        $this->sortEntries();
        $entriesCount = count($this->references);
        $string = "xref\n";
        $sectionStartEntry = 0;
        $writtenEntries = 0;
        while ($writtenEntries < $entriesCount) {
            // überspringe nicht vorhandene Einträge
            while (!$this->hasEntry($sectionStartEntry))
                ++ $sectionStartEntry;

            // zähle Einträge, die nacheinander sind
            $sectionEndEntry = $sectionStartEntry;
            while ($this->hasEntry($sectionEndEntry))
                ++ $sectionEndEntry;

            // schreibe die Sektion
            $string .= "{$sectionStartEntry} " . ($sectionEndEntry - $sectionStartEntry) . "\n";
            for ($i = $sectionStartEntry; $i < $sectionEndEntry; ++$i) {
                $string .= $this->getEntry($i)->toString();
            }

            // bereite nächste Sektion vor
            $writtenEntries += $sectionEndEntry - $sectionStartEntry;
            $sectionStartEntry = $sectionEndEntry;
        }
        return $string;
    }
}