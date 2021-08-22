<?php

namespace pdf\crossReference;

use pdf\indirectObject\PdfIndirectObject;

/**
 * Eintrag in einer CrossReferenceTable
 * @package pdf\crossReference
 */
class CrossReferenceTableEntry
{
    /**
     * Nummer des Objektes
     * @var int
     */
    protected $objNumber;
    /**
     * Generierungsnummer
     * @var int zwischen 0 und 65535 inklusive
     */
    protected $generationNumber;
    /**
     * Ob das Objekt genutzt (n/true) oder frei (f/false) ist
     * @var bool
     */
    protected $inUse;
    /**
     * Anzahl der Bytes bis zum Beginn des Objektes in der PDF-Datei
     * @var int
     */
    protected $byteOffset;
    /**
     * Das mit diesem Eintrag referenzierte Objekt
     * @var null|PdfIndirectObject
     */
    protected $referencedObject = null;

    /**
     * Erzeugt einen neuen Eintrag.
     * @param $objNumber int Nummer des Objektes
     * @param $generationNumber int Generierungsnummer zwischen 0 und 65535 inklusive
     * @param $inUse bool Ob das Objekt genutzt (n/true) oder frei (f/false) ist
     * @param $byteOffset int Anzahl der Bytes bis zum Beginn des Objektes in der PDF-Datei
     */
    public function __construct($objNumber, $generationNumber, $inUse, $byteOffset)
    {
        $this->objNumber = $objNumber;
        $this->generationNumber = $generationNumber;
        $this->inUse = $inUse;
        $this->byteOffset = $byteOffset;
    }

    /**
     * Gibt die eindeutige Nummer des Objektes zurück
     * @return int
     */
    public function getObjNumber(): int
    {
        return $this->objNumber;
    }

    /**
     * Gibt die Generierungsnummer des Objektes zurück
     * @return int
     */
    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    /**
     * Gibt zurück, ob dieses Objekt benutzt wird oder frei ist
     * @return bool
     */
    public function isInUse(): bool
    {
        return $this->inUse;
    }

    /**
     * Gibt die Anzahl der Bytes bis zum Beginn des Objektes in der PDF-Datei zurück
     * @return int
     */
    public function getByteOffset(): int
    {
        return $this->byteOffset;
    }

    /**
     * Gibt das in dem Eintrag referenzierte Objekt zurück
     * @return null|PdfIndirectObject
     */
    public function getReferencedObject()
    {
        return $this->referencedObject;
    }

    /**
     * Setzt die eindeutige Nummer des Objektes
     * @param int $objNumber
     */
    public function setObjNumber(int $objNumber): void
    {
        $this->objNumber = $objNumber;
        if ($this->referencedObject != null)
            $this->referencedObject->setObjectNumber($objNumber);
    }

    /**
     * Setzt die Generierungsnummer des Objektes
     * @param int $generationNumber
     */
    public function setGenerationNumber(int $generationNumber): void
    {
        $this->generationNumber = $generationNumber;
        if ($this->referencedObject != null)
            $this->referencedObject->setGenerationNumber($generationNumber);
    }

    /**
     * Setzt, ob diese Objektnummer frei ist oder benutzt wird
     * @param bool $inUse
     */
    public function setInUse(bool $inUse): void
    {
        $this->inUse = $inUse;
    }

    /**
     * Setzt den Byte Offset zu dem Indirect Object in der Datei
     * @param int $byteOffset
     */
    public function setByteOffset(int $byteOffset): void
    {
        $this->byteOffset = $byteOffset;
    }

    /**
     * Setzt das referenzierte Indirect Object
     * @param PdfIndirectObject $referencedObject
     */
    public function setReferencedObject(PdfIndirectObject $referencedObject): void
    {
        $this->referencedObject = $referencedObject;
        $this->referencedObject->setObjectNumber($this->objNumber);
        $this->referencedObject->setGenerationNumber($this->generationNumber);
        $this->inUse = true;
    }

    /**
     * Liefert einen (exakt 20 Zeichen langen) String zum einbetten in die CrossReferenceTable einer PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        return str_pad((string)$this->byteOffset, 10, "0", STR_PAD_LEFT)
            . " "
            . str_pad((string)$this->generationNumber, 5, "0", STR_PAD_LEFT)
            . " "
            . ($this->inUse ? "n" : "f")
            . " \n";
    }
}