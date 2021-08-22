<?php
namespace pdf\document;

use pdf\indirectObject\PdfStream;
use pdf\PdfFile;

/**
 * Abstrakte Klasse für Streams, die zur Dokumentenstruktur gehören.
 * Sie verweisen immer auf ein Dictionary, welches die Metadaten für dieses Objekt bereithält.
 * Zudem wird, als IndirectObject bezeichnet, auf den PdfStream verwiesen, der den Stream beinhaltet.
 * @package pdf\document
 */
abstract class AbstractDocumentStream extends AbstractDocumentObject
{
    /**
     * Überschreibt den Typ des IndirectObject als PdfStream
     * @var PdfStream
     */
    protected $indirectObject;

    public function __construct($pdfObject, PdfFile $pdfFile)
    {
        parent::__construct($pdfObject, $pdfFile);

        if ($this->indirectObject === null)
            throw new \Exception("No PdfStream given to Document Stream");
        if (!($this->indirectObject instanceof PdfStream))
            throw new \Exception("The given IndirectObject to this DocumentStream is no PdfStream");
    }

    /**
     * Liefert den Stream eines Dokument-Objektes
     * @return string
     * @throws \Exception Wenn der Stream nicht dekomprimiert werden kann
     */
    public function getStream() : string {
        return $this->indirectObject->getDecompressedStream();
    }

    /**
     * Setzt den Stream eines Dokument-Objektes
     * @param string $stream
     */
    public function setStream(string $stream) {
        $this->indirectObject->setDecompressedStream($stream);
    }
}