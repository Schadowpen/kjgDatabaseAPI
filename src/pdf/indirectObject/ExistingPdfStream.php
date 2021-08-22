<?php

namespace pdf\indirectObject;

use pdf\object\PdfDictionary;
use pdf\PdfFile;

/**
 * Ein Stream Object, welches bereits in der Pdf-Vorlage existiert
 * @package pdf\indirectObject
 */
class ExistingPdfStream extends PdfStream
{

    /**
     * Position in dem StringReader, bei der der Stream beginnt.
     * @var int
     */
    protected $streamStartPos;
    /**
     * Ob der Inhalt des Streams geändert wurde oder noch auf den Originalinhalt aus der PdfFile zugegriffen werden darf
     * @var bool
     */
    protected $streamAltered;


    /**
     * Konstruktor für einen Stream, welcher bereits in der Pdf-Vorlage existiert
     * @param int $objectNumber Positive Nummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param int $generationNumber Generierungsnummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param PdfDictionary $dictionary Dictionary, welches Metadaten über den Inhalt des PdfStreams bereithält
     * @param PdfFile $pdfFile PdfFile, in welchem der Stream sich befindet
     * @param int $decompressedStreamStartPos Byteposition im StringReader, bei dem der Stream beginnt.
     * @throws \Exception Wenn der Stream sich in einer externen Datei befinden soll
     */
    public function __construct(int $objectNumber, int $generationNumber, PdfDictionary $dictionary, PdfFile $pdfFile, int $decompressedStreamStartPos)
    {
        parent::__construct($objectNumber, $generationNumber, $dictionary, $pdfFile, "");
        if ($dictionary->hasObject("F"))
            throw new \Exception("Stream Content is in an external file. Only self-containing PDF-Files are allowed!");
        $this->streamStartPos = $decompressedStreamStartPos;
        $this->streamAltered = false;
    }

    public function getCompressedStream(): string
    {
        if ($this->streamAltered)
            return parent::getCompressedStream();

        $streamLength = $this->pdfFile->parseReference($this->containingObject->getObject("Length"))->getValue();
        return $this->pdfFile->getOriginalFileContent()->getSubstring($this->streamStartPos, $streamLength);
    }

    public function getDecompressedStream(): string
    {
        if ($this->streamAltered)
            return parent::getDecompressedStream();

        if ($this->decompressedStream === "")
            $this->decompressedStream = $this->decompressStream($this->getCompressedStream());
        return $this->decompressedStream;
    }

    public function setCompressedStream(string $compressedStream): void
    {
        parent::setCompressedStream($compressedStream);
        $this->streamAltered = true;
    }

    public function setDecompressedStream(string $decompressedStream): void
    {
        parent::setDecompressedStream($decompressedStream);
        $this->streamAltered = true;
    }
}