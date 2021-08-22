<?php

namespace pdf\document;


use pdf\indirectObject\PdfIndirectObject;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfDictionary;
use pdf\object\PdfIndirectReference;
use pdf\PdfFile;

/**
 * Abstrakte Klasse für Objekte, die zur Dokumentenstruktur gehören.
 * Sie verweisen immer auf ein Dictionary, welches die Daten für dieses Objekt bereithält.
 * Sollte das Dictionary direkt in einem IndirectObject liegen, kann dieses angegeben werden und Features wie Referenzierung genutzt werden.
 * @package pdf\document
 */
abstract class AbstractDocumentObject
{
    /**
     * Indirect Object, in welchem das Dictionary mit den Daten liegt.
     * Wenn es nicht existiert / nicht bekannt ist, ist der Wert null.
     * @var PdfIndirectObject|null
     */
    protected $indirectObject;

    /**
     * Dictionary mit den Daten über dieses Dokument Objekt
     * @var PdfDictionary
     */
    protected $dictionary;

    /**
     * PdfFile, in welchem dieses Objekt vorkommt
     * @var PdfFile
     */
    protected $pdfFile;

    /**
     * Erzeugt ein neues AbstractDocumentObject.
     * @param PdfDictionary|PdfIndirectObject|PdfIndirectReference $pdfObject Entweder ein Dictionary mit dem Objekt ODER ein IndirectObject, welches dieses Dictionary beinhaltet ODER eine Referenz auf das IndirectObject
     * @param PdfFile $pdfFile PDF-Datei, in welcher das Objekt liegt.
     * @throws \Exception Wenn das übergebene Dictionary kein Dictionary ist oder nicht dem richtigen Objekttyp entspricht.
     */
    public function __construct($pdfObject, PdfFile $pdfFile)
    {
        if ($pdfObject instanceof PdfIndirectObject) {
            $this->indirectObject = $pdfObject;
            $this->dictionary = $pdfObject->getContainingObject();
        } else if ($pdfObject instanceof PdfIndirectReference) {
            $this->indirectObject = $pdfFile->getIndirectObject($pdfObject);
            $this->dictionary = $this->indirectObject->getContainingObject();
        } else {
            $this->dictionary = $pdfObject;
        }
        if (!($this->dictionary instanceof PdfDictionary))
            throw new \Exception("Document Object is no Dictionary");

        if (!self::matchesType($this->dictionary))
            throw new \Exception("Given Document does not match Type " . self::objectType() . (self::objectSubtype() !== null ? " with Subtype" . self::objectSubtype() : ""));

        $this->pdfFile = $pdfFile;
    }

    /**
     * Liefert den Type dieser Klasse an Dokument Objekten.
     * Er sollte in einem zugehörigen Dictionary immer unter Type enthalten sein.
     * <br/>
     * Sollte für die Objektklasse kein Type-Attribut benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static abstract function objectType(): ?string;

    /**
     * Liefert den Subtype dieser Klasse an Dokument Objekten.
     * Sollte für die Dokumentklasse kein SubType benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static abstract function objectSubtype(): ?string;

    /**
     * Liefert zurück, ob das durch das Dictionary beschriebene Objekt diesen Objekttyp besitzt.
     * Dies wird anhand des Type und Subtype-Eintrages bestimmt.
     * @param PdfDictionary $dictionary Dictionary, welches ein Dokumentenobjekt beinhaltet
     * @return bool
     */
    public static function matchesType(PdfDictionary $dictionary): bool
    {
        if (static::objectType() === null)
            return true; // Kein Type angegeben -> alles passt
        $type = $dictionary->getObject("Type");
        $subtype = $dictionary->getObject("Subtype");
        return @$type != null && $type->getValue() === static::objectType()
            && ($subtype === null ? static::objectSubtype() === null : $subtype->getValue() === static::objectSubtype());
    }

    /**
     * Gibt das Indirect Object zurück, in welchem das Dictionary mit den Daten liegt.
     * Wenn es nicht existiert / nicht bekannt ist, wird null zurückgegeben.
     * @return PdfIndirectObject
     */
    public function getIndirectObject(): ?PdfIndirectObject
    {
        return $this->indirectObject;
    }

    /**
     * Gibt eine IndirectReference auf dieses Objekt zurück.
     * Sollte dem Dokumentenobjekt kein IndirectObject zugeordnet sein, wird ein neues IndirectObject erzeugt.
     * @return PdfIndirectReference Indirect Reference auf dieses Objekt
     */
    public function getIndirectReference(): PdfIndirectReference {
        $this->generateIndirectObjectIfNotExists();
        return $this->indirectObject->getIndirectReference();
    }

    /**
     * Gibt das Dictionary mit den Daten über dieses Dokument Objekt zurück
     * @return PdfDictionary
     */
    public function getDictionary(): PdfDictionary
    {
        return $this->dictionary;
    }

    /**
     * Liefert das PdfFile zurück, in welchem das Dokument Objekt vorkommt
     * @return PdfFile
     */
    public function getPdfFile(): PdfFile
    {
        return $this->pdfFile;
    }

    /**
     * Kurzform, um einen Eintrag aus dem Dictionary zu holen.
     * Referenzen werden direkt zu einem Objekt geparst.
     * @param string $dictionaryName Name des Eintrags im Dictionary
     * @return PdfAbstractObject|null
     */
    public function get(string $dictionaryName) : ?PdfAbstractObject
    {
        return $this->pdfFile->parseReference($this->dictionary->getObject($dictionaryName));
    }

    /**
     * Kurzform, um einen Eintrag in das Dictionary zu schreiben
     * @param string $dictionaryName Name des Eintrags im Dictionary
     * @param PdfAbstractObject $object Neues Objekt, welches eingetragen werden soll
     */
    public function set(string $dictionaryName, PdfAbstractObject $object) {
        $this->dictionary->setObject($dictionaryName, $object);
    }

    /**
     * Kurzform, um einen Eintrag aus dem Dictionary zu löschen
     * @param string $dictionaryName Name des Eintrags im Dictionary
     */
    public function remove(string $dictionaryName) {
        $this->dictionary->removeObject($dictionaryName);
    }


    /**
     * Wenn zu diesem Dokument Objekt noch kein dazugehöriges IndirectObject bekannt ist, wird es erstellt und in die PdfFile eingebettet.
     * Sollte sich dieses Objekt bereits innerhalb eines anderen Objektes befinden und dies hier nicht bekannt sein, kann diese Funktion zu unerwartetem Verhalten führen.
     */
    public function generateIndirectObjectIfNotExists()
    {
        if ($this->indirectObject === null) {
            $crossReferenceTableEntry = $this->pdfFile->generateNewCrossReferenceTableEntry();
            $this->indirectObject = new PdfIndirectObject($crossReferenceTableEntry->getObjNumber(), $crossReferenceTableEntry->getGenerationNumber(), $this->dictionary);
            $crossReferenceTableEntry->setReferencedObject($this->indirectObject);
        }
    }
}