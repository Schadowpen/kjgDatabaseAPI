<?php


namespace pdf\indirectObject;

use pdf\object\PdfDictionary;
use pdf\object\PdfName;
use pdf\object\PdfNumber;
use pdf\PdfFile;

/**
 * Klasse zum Aufbauen eines neuen ObjectStreams mit einigen Objekten.
 * Es wird angenommen, dass nur die Funktion addIndirectObject() von außen benutzt wird.
 * @package pdf\indirectObject
 */
class ObjectStream extends PdfStream
{
    protected $objectStreamNumbers = "";
    protected $objectStreamContent = "";

    /**
     * ObjectStream constructor.
     * @param int $objectNumber Positive Nummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param int $generationNumber Generierungsnummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param PdfFile $pdfFile PdfFile, in welchem der Stream sich befindet
     * @throws \Exception Wird nie geschmissen
     */
    public function __construct(int $objectNumber, int $generationNumber, PdfFile $pdfFile)
    {
        parent::__construct(
            $objectNumber,
            $generationNumber,
            new PdfDictionary([
                "Type" => new PdfName("ObjStm"),
                "N" => new PdfNumber(0),
                "First" => new PdfNumber(0),
                "Filter" => new PdfName("FlateDecode")
            ]),
            $pdfFile,
            "");
    }

    /**
     * Fügt ein neues IndirectObject diesem ObjectStream hinzu.
     * Es wird angenommen, dass das Objekt den Regeln für IndirectObjects in ObjectStreams genügt und auch nicht im Body vorkommt.
     * @param PdfIndirectObject $indirectObject
     * @return mixed Index des hinzugefügten Objektes im Object Stream
     */
    public function addIndirectObject(PdfIndirectObject $indirectObject) {
        // Berechne Index und Anzahl Objekte
        $index = $this->containingObject->getObject("N")->getValue();
        $this->containingObject->setObject("N", new PdfNumber($index + 1));

        // Füge in Teilstrings hinzu
        $offsetInStream = strlen($this->objectStreamContent);
        $this->objectStreamNumbers .= "{$indirectObject->getObjectNumber()} {$offsetInStream}\n";
        $this->objectStreamContent .= $indirectObject->getContainingObject()->toString() . "\n";

        // Aktualisiere kompletten Streaminhalt und Daten
        $this->decompressedStream = $this->objectStreamNumbers . $this->objectStreamContent;
        $this->containingObject->setObject("First", new PdfNumber(strlen($this->objectStreamNumbers)));

        return $index;
    }
}