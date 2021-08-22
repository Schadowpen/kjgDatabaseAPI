<?php

namespace pdf\indirectObject;

use pdf\crossReference\CrossReferenceTableEntry;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfDictionary;
use pdf\object\PdfName;
use pdf\object\PdfNumber;
use pdf\PdfFile;

/**
 * Ein Stream-Objekt einer Pdf-Datei. Es erweitert ein Indirect Object
 * @package pdf\indirectObject
 */
class PdfStream extends PdfIndirectObject
{
    /**
     * Dictionary mit Metadaten über den Stream
     * Überschreibt $value aus PdfIndirectObject
     * @var PdfDictionary
     */
    protected $containingObject;
    /**
     * Komprimierter Stream
     * @var string|null
     */
    protected $compressedStream = null;
    /**
     * Dekomprimierter Stream
     * @var string|null
     */
    protected $decompressedStream = null;
    /**
     * PdfFile, in welchem sich der Stream befindet
     * @var PdfFile
     */
    protected $pdfFile;

    /**
     * PdfStream constructor.
     * @param int $objectNumber Positive Nummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param int $generationNumber Generierungsnummer des Indirekten Objektes, Teil des Objekt Identifiers
     * @param PdfDictionary $dictionary Dictionary, welches Metadaten über den Inhalt des PdfStreams bereithält
     * @param PdfFile $pdfFile PdfFile, in welchem der Stream sich befindet
     * @param string $decompressedStream Byte-Inhalt des Streams
     */
    public function __construct(int $objectNumber, int $generationNumber, PdfDictionary $dictionary, PdfFile $pdfFile, string $decompressedStream)
    {
        parent::__construct($objectNumber, $generationNumber, $dictionary);
        $this->pdfFile = $pdfFile;
        $this->decompressedStream = $decompressedStream;
    }

    /**
     * Erzeugt einen neuen PdfStream aus einem CrossReferenceTableEntry.
     * Der Stream ist mit dem FlateDecode-Filter komprimiert.
     * @param CrossReferenceTableEntry $crossReferenceTableEntry Bislang ungenutzter CrossReferenceTableEntry
     * @param PdfFile $pdfFile PdfFile, in welcher der Stream vorkommt
     * @param string $decompressedStream Streaminhalt. Wenn nicht angegeben, wird ein leerer String genutzt
     * @return PdfStream Eine neue Instanz eines PdfStreams
     * @throws \Exception Wird nie geschmissen
     */
    public static function fromCrossReferenceTableEntry(CrossReferenceTableEntry $crossReferenceTableEntry, PdfFile $pdfFile, string $decompressedStream = "") {
        $pdfStream = new PdfStream(
            $crossReferenceTableEntry->getObjNumber(),
            $crossReferenceTableEntry->getGenerationNumber(),
            new PdfDictionary(["Filter" => new PdfName("FlateDecode")]),
            $pdfFile,
            $decompressedStream
        );
        $crossReferenceTableEntry->setReferencedObject($pdfStream);
        return $pdfStream;
    }

    /**
     * Liefert die Filter, mit welchen der Stream Kodiert ist.<br/>
     * Wird ein PdfName zurückgegeben, wurde nur ein Filter zum dekodieren verwendet.
     * Wird ein PdfArray aus PdfNames zurückgegeben, wurden entsprechend der Länge des Arrays Filter verwendet.
     * Wird null zurückgegeben, wurde kein Filter angewendet.
     * @return PdfName|PdfArray|null
     */
    public function getFilter(): ?PdfAbstractObject
    {
        return $this->pdfFile->parseReference($this->containingObject->getObject("Filter"));
    }

    /**
     * Liefert die Parameter zum Dekodieren der entsprechenden Filter.<br/>
     * Ist das Dictionary mit den Parametern für den entsprechenden Filter null, sind die Parameter auf Standardwerten
     * @return PdfDictionary|PdfArray|null
     * @see PdfStream::getFilter()
     */
    public function getDecodeParms(): ?PdfAbstractObject
    {
        return $this->pdfFile->parseReference($this->containingObject->getObject("DecodeParms"));
    }

    /**
     * Liefert den Komprimierten Stream
     * @return string
     * @throws \Exception Wenn der Stream nicht komprimiert werden kann
     */
    public function getCompressedStream(): string
    {
        if ($this->compressedStream === null)
            $this->compressedStream = $this->compressStream($this->decompressedStream);
        return $this->compressedStream;
    }

    /**
     * Komprimiert den Stream
     * @param string|null $streamString Der dekomprimierte Stream-Inhalt
     * @return string Der komprimierte Stream-Inhalt
     * @throws \Exception Wenn der Stream nicht komprimiert werden kann
     */
    protected function compressStream(?string $streamString)
    {
        if ($streamString === null)
            throw new \Exception("No decompressed Stream to decompress available");
        $filter = $this->getFilter();
        $decodeParms = $this->getDecodeParms();

        if ($filter instanceof PdfName) {
            $streamString = $this->compressFilter($filter, $decodeParms, $streamString);

        } else if ($filter instanceof PdfArray) {
            for ($i = $filter->getArraySize() - 1; $i >= 0; --$i) {
                /** @var PdfName $currentFilter */
                $currentFilter = $filter->getObject($i);
                /** @var PdfDictionary|null $currentDecodeParms */
                $currentDecodeParms = $decodeParms->getObject($i);
                $streamString = $this->compressFilter($currentFilter, $currentDecodeParms, $streamString);
            }
        }
        return $streamString;
    }

    /**
     * Komprimiert einen Filter eines Streams
     * @param PdfName $filter Name des Filters
     * @param PdfDictionary|null $decodeParms Parameter zum dekodieren des Filters
     * @param string $decoded Der dekomprimierte Stream-Inhalt
     * @return string Der komprimierte Stream-Inhalt
     * @throws \Exception Wenn der Filter nicht implementiert ist.
     */
    protected function compressFilter(PdfName $filter, ?PdfDictionary $decodeParms, string $decoded)
    {
        switch ($filter->getValue()) {
            case "FlateDecode":
                return FlateDecode::compress($decoded, $decodeParms);
            default:
                throw new \Exception("This PDF uses an unsupported compression technique");
        }
    }

    /**
     * Liefert den dekomprimierten Stream
     * @return string
     * @throws \Exception Wenn der Stream nicht dekomprimiert werden kann
     */
    public function getDecompressedStream(): string
    {
        if ($this->decompressedStream === null)
            $this->decompressedStream = $this->decompressStream($this->compressedStream);
        return $this->decompressedStream;
    }

    /**
     * Dekomprimiert den Stream
     * @param string|null $streamString Der komprimierte Stream-Inhalt
     * @return string Der dekomprimierte Stream-Inhalt
     * @throws \Exception Wenn der Stream nicht dekomprimiert werden kann
     */
    protected function decompressStream(?string $streamString)
    {
        if ($streamString === null)
            throw new \Exception("No compressed Stream to decompress available");
        $filter = $this->getFilter();
        $decodeParms = $this->getDecodeParms();

        if ($filter instanceof PdfName) {
            $streamString = $this->decompressFilter($filter, $decodeParms, $streamString);

        } else if ($filter instanceof PdfArray) {
            $filterCount = $filter->getArraySize();
            for ($i = 0; $i < $filterCount; ++$i) {
                /** @var PdfName $currentFilter */
                $currentFilter = $filter->getObject($i);
                /** @var PdfDictionary|null $currentDecodeParms */
                $currentDecodeParms = $decodeParms->getObject($i);
                $streamString = $this->decompressFilter($currentFilter, $currentDecodeParms, $streamString);
            }
        }
        return $streamString;
    }

    /**
     * Dekomprimiert einen Filter eines Streams
     * @param PdfName $filter Name des Filters
     * @param PdfDictionary|null $decodeParms Parameter zum dekodieren des Filters
     * @param string $encoded Der Komprimierte Stream-Inhalt
     * @return string Der dekomprimierte Stream-Inhalt
     * @throws \Exception Wenn der Filter nicht implementiert ist.
     */
    protected function decompressFilter(PdfName $filter, ?PdfDictionary $decodeParms, string $encoded)
    {
        switch ($filter->getValue()) {
            case "FlateDecode":
                return FlateDecode::decompress($encoded, $decodeParms);
            default:
                throw new \Exception("This PDF uses an unsupported compression technique");
        }
    }


    /**
     * Setzt den Komprimierten Stream auf einen neuen Wert
     * @param string $compressedStream
     */
    public function setCompressedStream(string $compressedStream): void
    {
        $this->compressedStream = $compressedStream;
        $this->decompressedStream = null;
    }

    /**
     * Setzt den Unkomprimierten Stream auf einen neuen Wert
     * @param string $decompressedStream
     */
    public function setDecompressedStream(string $decompressedStream): void
    {
        $this->decompressedStream = $decompressedStream;
        $this->compressedStream = null;
    }

    /**
     * Erstellt für diesen Stream einen String zum Einbetten in eine PDF-Datei
     * @return string
     * @throws \Exception Sollte kein String erstellt werden können
     */
    public function toString(): string
    {
        $compressedStream = $this->getCompressedStream();
        $this->containingObject->setObject("Length", new PdfNumber(strlen($compressedStream)));

        $string = "{$this->objectNumber} {$this->generationNumber} obj\n";
        $string .= $this->containingObject->toString() . "\n";
        $string .= "stream\n";
        $string .= $compressedStream . "\n";
        $string .= "endstream\n";
        $string .= "endobj\n";
        return $string;
    }
}