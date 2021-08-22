<?php

namespace pdf;

use misc;
use pdf\crossReference\CompressedObjectCrossReferenceTableEntry;
use pdf\crossReference\CrossReferenceTable;
use pdf\crossReference\CrossReferenceTableEntry;
use pdf\crossReference\ExistingCrossReferenceTableEntry;
use pdf\indirectObject\ExistingPdfStream;
use pdf\indirectObject\IndirectObjectParser;
use pdf\indirectObject\ObjectStream;
use pdf\indirectObject\PdfIndirectObject;
use pdf\indirectObject\PdfStream;
use pdf\object\ObjectParser;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfDictionary;
use pdf\object\PdfName;
use pdf\object\PdfNull;
use pdf\object\PdfIndirectReference;
use pdf\object\PdfNumber;

/**
 * Klasse zum Einlesen und Bearbeiten einer PDF-Datei
 */
class PdfFile
{
    /**
     * Alle Einträge, die in einem Trailer Dictionary vorkommen können.
     * @var string[]
     */
    public const trailerDictionaryKeys = ["Size", "Prev", "Root", "Encrypt", "Info", "ID"];

    /**
     * Inhalt der originalen PDF-Datei.
     * Wurde der Inhalt geändert, entspricht dies nicht mehr exakt dem Inhalt.
     * @var misc\StringReader
     */
    protected $originalFileContent;

    /**
     * CrossReferenceTable mit allen Objekten dieser PDF-Datei.
     * Sollten im Original mehrere CrossReferenceTables vorkommen, werden diese hier zusammengelegt.
     * @var CrossReferenceTable
     */
    protected $crossReferenceTable;

    /**
     * Das Trailer Dictionary dieser Pdf-Datei.
     * Sollte es mehrere Dictionarys geben, wird das neueste genommen und der Prev-Eintrag entfernt.
     * @var PdfDictionary
     */
    protected $trailerDictionary;

    /**
     * Version der PDF-Datei (1.0 bis 1.7)
     * @var string
     */
    protected $fileVersion;

    /**
     * Erzeugt ein PdfFile aus einer existierenden PDF-Datei oder einem StringReader mit dem Inhalt der PDF-Datei
     * @param string|misc\StringReader $pdfSource Name und Pfad der PDF-Datei als String oder StringReader mit PDF-Inhalt
     * @throws \Exception Wenn etwas mit der PDF-Datei nicht in Ordnung ist
     */
    public function __construct($pdfSource)
    {
        try {
            if ($pdfSource instanceof misc\StringReader) {
                $fileString = $pdfSource->getString();
                $this->originalFileContent = $pdfSource;
            } else {
                // Lese Datei
                $fileString = @file_get_contents($pdfSource);
                if ($fileString === false)
                    throw new \Exception("could not read file " . $pdfSource);
                $this->originalFileContent = new misc\StringReader($fileString);
            }

            // Lese Version, checke nebenbei, ob es sich um eine gültige PDF-Datei handelt
            if (substr($fileString, 0, 5) != "%PDF-" || $fileString[6] != ".")
                throw new \Exception("this file is not a supported PDF file");

            $this->setVersion(substr($fileString, 5, 3));

            // Finde Cross Reference Table
            $posOfEOF = strrpos($fileString, "%%EOF");
            // suche die Zeile davor
            if ($fileString[$posOfEOF - 2] === "\r" && $fileString[$posOfEOF - 1] === "\n")
                $prevLineEnd = $posOfEOF - 2;
            else
                $prevLineEnd = $posOfEOF - 1;
            $crPos = strrpos($fileString, "\r", $prevLineEnd - $this->getOriginalFileContentLength() - 1);
            $lfPos = strrpos($fileString, "\n", $prevLineEnd - $this->getOriginalFileContentLength() - 1);
            $prevLineStart = max($crPos, $lfPos) + 1;
            $startOfXref = (int)substr($fileString, $prevLineStart, $prevLineEnd - $prevLineStart);

            // Lese Cross Reference Table und Trailer Dictionary
            $this->crossReferenceTable = new crossReference\CrossReferenceTable();
            $this->trailerDictionary = $this->extractPdfFileEnd($startOfXref);
            $this->trailerDictionary->removeObject("Prev");

            // Erkenne wenn Dokument verschlüsselt ist
            if ($this->trailerDictionary->hasObject("Encrypt"))
                throw new \Exception("Encrypted Documents are not supported!");

            // Entferne alle Einträge der CrossReferenceTable, die größer als die angegebene Size sind
            $size = $this->trailerDictionary->getObject("Size")->getValue();
            foreach ($this->crossReferenceTable->getAll() as $crossReferenceTableEntry) {
                if ($crossReferenceTableEntry->getObjNumber() >= $size)
                    $this->crossReferenceTable->removeEntry($crossReferenceTableEntry->getObjNumber());
            }

            // Checke Katalog Dictionary nach einem Version Eintrag (PDF 1.4)
            /** @var PdfDictionary $katalogDictionary */
            $katalogDictionary = $this->parseReference($this->trailerDictionary->getObject("Root"));
            if ($katalogDictionary->hasObject("Version")) {
                $this->fileVersion = max($katalogDictionary->getObject("Version")->getValue(), $this->fileVersion);
                $katalogDictionary->removeObject("Version"); // wird in der bearbeiteten PdfFile über den Header geregelt
            }
            // $katalogDictionary->hasobject("Extensions") falls Extensions wichtig werden

        } catch (\Error $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Extrahiert das Ende der Pdf-Datei, bestehend aus CrossReferenceTable und Trailer.
     * Die Einträge der CrossReferenceTable werden der CrossReferenceTable dieses Objektes hinzugefügt, sofern noch nicht vorhanden.
     * Das Trailer Dictionary wird zurückgeliefert.
     * Sollte das Trailer Dictionary auf eine vorherige CrossReferenceTable samt Trailer verweisen, wird diese ebenfalls extrahiert.
     * @param int $startOfXref Start der CrossReferenceTable mit einem 'xref'
     * @return object\PdfDictionary
     * @throws \Exception Wenn das PdfFileEnd nicht geparst werden kann.
     */
    protected function extractPdfFileEnd($startOfXref)
    {
        $stringReader = $this->originalFileContent;
        $stringReader->setReaderPos($startOfXref);

        if ($stringReader->readLine() === 'xref') {
            // Lese Cross Reference Table aus
            $readedLine = $stringReader->readLine();
            while ($readedLine !== 'trailer') {
                // Lese Länge der Subsection
                $lineContents = explode(" ", $readedLine);
                $sectionStart = (int)$lineContents[0];
                $sectionLength = (int)$lineContents[1];

                // Lese alle einzelnen Einträge, füge hinzu sofern noch nicht geschehen.
                for ($i = $sectionStart; $i < $sectionStart + $sectionLength; ++$i) {
                    $readedLine = $stringReader->readSubstring(20);
                    if (!$this->crossReferenceTable->hasEntry($i)) {
                        $this->crossReferenceTable->setEntry(new crossReference\ExistingCrossReferenceTableEntry(
                            $i,
                            (int)substr($readedLine, 11, 5),
                            $readedLine[17] === 'n',
                            (int)substr($readedLine, 0, 10),
                            $this
                        ));
                    }
                }

                // Navigiere zur nächsten Subsection
                $readedLine = $stringReader->readLine();
            }

            // Lese Trailer Dictionary aus
            $trailerDictionary = (new ObjectParser($stringReader))->parseObject();
            if (!($trailerDictionary instanceof PdfDictionary))
                throw new \Exception("Trailer Dictionary is no Dictionary");

            // Finde vorheriges PdfFileEnd, sofern vorhanden
            if ($trailerDictionary->hasObject("Prev")) {
                $prevEntry = $trailerDictionary->getObject("Prev");
                $this->extractPdfFileEnd($prevEntry->getValue());
            }
            return $trailerDictionary;
        }

        // Cross-Reference Stream auslesen
        /** @var PdfStream $crossReferenceStream */
        $crossReferenceStream = IndirectObjectParser::parse($this, $startOfXref);
        /** @var PdfDictionary $streamDictionary */
        $streamDictionary = $crossReferenceStream->getContainingObject();
        if (!($crossReferenceStream instanceof PdfStream) || $streamDictionary->getObject("Type")->getValue() !== "XRef")
            throw new \Exception("Could not find neither Cross Reference Table nor Cross Reference Stream");

        /** @var PdfArray $indexArray */
        $indexArray = $streamDictionary->getObject("Index");
        if ($indexArray === null)
            $indexArray = new PdfArray([0, $streamDictionary->getObject("Size")->getValue()]);
        /** @var PdfNumber[] $fieldByteSizes */
        $fieldByteSizes = $streamDictionary->getObject("W")->getValue();
        if (count($fieldByteSizes) !== 3)
            throw new \Exception("W Entry in Cross Reference Stream contains " . count($fieldByteSizes) . " entries");
        $typeByteSize = $fieldByteSizes[0]->getValue();
        $field2ByteSize = $fieldByteSizes[1]->getValue();
        $field3ByteSize = $fieldByteSizes[2]->getValue();
        $stringReader = new misc\StringReader($crossReferenceStream->getDecompressedStream()); // StringReader liest nun Stream aus

        // Alle Subsektionen mit allen Einträgen auslesen.
        $objectStreams = [];
        $indexArraySize = $indexArray->getArraySize();
        for ($i = 0; $i < $indexArraySize; $i += 2) {
            $startObjectNumber = $indexArray->getObject($i)->getValue();
            $endObjectNumber = $startObjectNumber + $indexArray->getObject($i + 1)->getValue();
            for ($objectNumber = $startObjectNumber; $objectNumber < $endObjectNumber; ++$objectNumber) {
                $type = $typeByteSize > 0 ? $this->stringToInt($stringReader->readSubstring($typeByteSize)) : 1;
                $field2 = $field2ByteSize > 0 ? $this->stringToInt($stringReader->readSubstring($field2ByteSize)) : 0;
                $field3 = $field3ByteSize > 0 ? $this->stringToInt($stringReader->readSubstring($field3ByteSize)) : 0;
                if (!$this->crossReferenceTable->hasEntry($objectNumber)) {
                    switch ($type) {
                        case 0:
                            $this->crossReferenceTable->setEntry(new ExistingCrossReferenceTableEntry(
                                $objectNumber,
                                $field3,
                                false,
                                0,
                                $this
                            ));
                            break;
                        case 1:
                            $this->crossReferenceTable->setEntry(new ExistingCrossReferenceTableEntry(
                                $objectNumber,
                                $field3,
                                true,
                                $field2,
                                $this
                            ));
                            break;
                        case 2:
                            if (array_search($field2, $objectStreams) === false)
                                array_push($objectStreams, $field2);
                            $this->crossReferenceTable->setEntry(new CrossReferenceTableEntry(
                                $objectNumber,
                                0,
                                true,
                                -1
                            ));
                            break;
                    }
                }
            }
        }

        // Verschieben der Schlüssel für das TrailerDictionary
        $trailerDictionary = new PdfDictionary([]);
        foreach (self::trailerDictionaryKeys as $trailerDictionaryKey) {
            $trailerDictionary->setObject($trailerDictionaryKey, $streamDictionary->getObject($trailerDictionaryKey));
            if ($trailerDictionaryKey !== "Size" && $trailerDictionaryKey !== "Prev")
                $streamDictionary->removeObject($trailerDictionaryKey);
        }

        // Finde vorheriges PdfFileEnd, sofern vorhanden
        if ($trailerDictionary->hasObject("Prev")) {
            $prevEntry = $trailerDictionary->getObject("Prev");
            $this->extractPdfFileEnd($prevEntry->getValue());
        }

        // Alle Object Streams auslesen
        foreach ($objectStreams as $objectStreamNumber) {
            $crossReferenceTableEntry = $this->getCrossReferenceTableEntry($objectStreamNumber, 0);
            if ($crossReferenceTableEntry !== null)
                $this->readObjectStream($crossReferenceTableEntry);
        }

        return $trailerDictionary;
    }

    /**
     * Liest den Object Stream hinter dem übergebenen CrossReferenceTableEntry aus
     * und speichert die gefundenen Objekte in bereits vorhandenen CrossReferenceTableEntries
     * @param CrossReferenceTableEntry $crossReferenceTableEntry Eintrag mit dem ObjectStream
     * @throws \Exception Wenn der ObjectStream nicht gelesen werden kann.
     */
    private function readObjectStream(CrossReferenceTableEntry $crossReferenceTableEntry)
    {
        /** @var PdfStream $objectStream */
        $objectStream = $crossReferenceTableEntry->getReferencedObject();
        if (!$objectStream instanceof PdfStream)
            throw new \Exception("Object Stream '{$crossReferenceTableEntry->getObjNumber()} {$crossReferenceTableEntry->getGenerationNumber()} obj' is not Stream");
        /** @var PdfDictionary $streamDictionary */
        $streamDictionary = $objectStream->getContainingObject();
        $streamString = $objectStream->getDecompressedStream();
        $numberOfObjects = $streamDictionary->getObject("N")->getValue();
        $byteOffsetToFirst = $streamDictionary->getObject("First")->getValue();
        $objectPositionParser = new ObjectParser(new misc\StringReader($streamString), 0);
        $objectParser = new ObjectParser(new misc\StringReader($streamString));

        // Alle Objekte nacheinander auslesen
        for ($i = 0; $i < $numberOfObjects; $i++) {
            // Nummer und Position aus dem Anfang des Streams auslesen
            $objectNumber = $objectPositionParser->parseObject()->getValue();
            $objectByteOffset = $objectPositionParser->parseObject()->getValue();

            // Objekt aus dem Stream auslesen und dem zugehörigen CrossReferenceTableEntry übergeben
            $objectParser->getStringReader()->setReaderPos($byteOffsetToFirst + $objectByteOffset);
            $object = $objectParser->parseObject();
            $objectCRTE = $this->crossReferenceTable->getEntry($objectNumber);
            if ($objectCRTE !== null)
                $objectCRTE->setReferencedObject(new PdfIndirectObject(
                    $objectCRTE->getObjNumber(),
                    $objectCRTE->getGenerationNumber(),
                    $object
                ));
        }

        // Object Stream löschen
        $this->crossReferenceTable->removeEntry($crossReferenceTableEntry->getObjNumber());

        // Extends Object Stream, sofern verfügbar
        if ($streamDictionary->hasObject("Extends")) {
            /** @var PdfIndirectReference $indirectReference */
            $indirectReference = $streamDictionary->getObject("Extends");
            $extendsCRTE = $this->getCrossReferenceTableEntry($indirectReference->getObjectNumber(), $indirectReference->getGenerationNumber());
            if ($extendsCRTE !== null)
                $this->readObjectStream($extendsCRTE);
        }
    }

    /**
     * Konvertiert einen Byte String (Big Endian) in einen Integer
     * @param string $string Byte String
     * @return int In dem Byte String kodierte Zahl
     */
    private function stringToInt(string $string): int
    {
        $stringLength = strlen($string);
        $int = 0;
        for ($i = 0; $i < $stringLength; ++$i) {
            $int = $int * 256 + ord($string[$i]);
        }
        return $int;
    }

    /**
     * Konvertiert einen Integer in einen Byte String (Big Endian) mit einer bestimmten Länge.
     * @param int $int Zu kodierende Zahl
     * @param int $strlen Länge des zurückzugebenden Strings
     * @return string Byte String (Big Endian)
     */
    private function intToString(int $int, int $strlen): string
    {
        $string = "";
        for ($i = $strlen; $i > 0; --$i) {
            $string = chr($int % 256) . $string;
            $int /= 256;
        }
        return $string;
    }

    /**
     * Liefert die Anzahl der Bytes, die mindestens benötigt würden, um diesen integer abzuspeichern.
     * @param int $integer positiver Integer
     * @return int Anzahl der mindestens benötigten Bytes
     * @see PdfFile::intToString()
     */
    private function getNeededBytes(int $integer)
    {
        $neededBytes = 0;
        $maxValue = 1;
        while ($integer >= $maxValue) {
            ++$neededBytes;
            $maxValue *= 256;
        }
        return $neededBytes;
    }

    /**
     * Generiert aus der PdfFile eine PDF-Datei.
     * Nach Aufruf dieser Funktion sollte die PdfFile und dazugehörige Objekte besser nicht mehr verwendet werden, da während des Speicherns vor allem Bytepositionen neu gesetzt werden.
     * @param bool $allowDataCompression Ob erlaubt werden soll, die Daten in Object Streams zu komprimieren, Standardwert true
     * @return string Inhalt der PDF-Datei
     * @throws \Exception Wenn die Datei nicht gespeichert werden konnte
     */
    public function savePdfFile(bool $allowDataCompression = true)
    {
        // Eintrag Nummer 0 muss vorhanden sein
        $neededEntry = $this->crossReferenceTable->getEntry(0);
        if ($neededEntry !== null && $neededEntry->isInUse())
            throw new \Exception("Cross Reference Table Entry Number 0 shall not be used");
        if ($neededEntry === null || $neededEntry->getGenerationNumber() !== 65535)
            $this->crossReferenceTable->setEntry(new CrossReferenceTableEntry(0, 65535, false, 0));

        // Überprüfen, ob ObjectStreams und CrossReferenceStreams genutzt werden sollen
        $useObjectStreams = $allowDataCompression && $this->fileVersion >= "1.5";
        if ($useObjectStreams)
            $objectStream = new ObjectStream($this->trailerDictionary->getObject("Size")->getValue(), 0, $this);

        // Header
        $fileContent = "%PDF-" . $this->fileVersion . "\n"
            . "%\xA0\xBC\xDE\xFE\n";
        $currentBytePos = strlen($fileContent);

        // Body
        $this->crossReferenceTable->sortEntries();
        $crossReferenceTableEntries = $this->crossReferenceTable->getAll();
        foreach ($crossReferenceTableEntries as $crossReferenceTableEntry) {
            // überprüfe, ob Eintrag geschrieben werden soll
            if (!$crossReferenceTableEntry->isInUse())
                continue;
            $referencedObject = $crossReferenceTableEntry->getReferencedObject();
            if ($referencedObject == null)
                continue;

            if ($useObjectStreams
                && $crossReferenceTableEntry->getGenerationNumber() === 0
                && !($referencedObject instanceof PdfStream)
                && ($referencedObject->getContainingObject() instanceof PdfDictionary || $referencedObject->getContainingObject() instanceof PdfArray)) {
                // Baue Eintrag in ObjectStream ein und merke dies
                $indexInObjectStream = $objectStream->addIndirectObject($referencedObject);
                $this->crossReferenceTable->setEntry(new CompressedObjectCrossReferenceTableEntry(
                    $crossReferenceTableEntry->getObjNumber(),
                    $objectStream->getObjectNumber(),
                    $crossReferenceTableEntry->isInUse(),
                    $indexInObjectStream
                ));

            } else {
                // schreibe Eintrag und behalte den Byte Offset im Auge
                $crossReferenceTableEntry->setByteOffset($currentBytePos);
                $objectString = $referencedObject->toString();
                $currentBytePos += strlen($objectString);
                $fileContent .= $objectString;
            }
        }

        if ($currentBytePos < 2500000) {
            // Easter Egg
            $fileContent .= ""
                . "%  _______________ \n"
                . "% |               |\n"
                . "% | Dieses Schild |\n"
                . "% |  bitte nicht  |\n"
                . "% |   beachten!   |\n"
                . "% |_______________|\n"
                . "%        ||        \n"
                . "%        ||        \n"
                . "%        ||        \n"
                . "%        ||        \n";
            $currentBytePos += 200;
        }

        if ($useObjectStreams) {
            // Speichere Object Stream
            $crossReferenceTableEntry = $this->generateNewCrossReferenceTableEntry();
            $crossReferenceTableEntry->setByteOffset($currentBytePos);
            $crossReferenceTableEntry->setReferencedObject($objectStream);
            $objectStream->setObjectNumber($crossReferenceTableEntry->getObjNumber());
            $objectStream->setGenerationNumber($crossReferenceTableEntry->getGenerationNumber());
            $objectString = $objectStream->toString();
            $currentBytePos += strlen($objectString);
            $fileContent .= $objectString;

            // Erzeuge CrossReference Stream
            $crossReferenceStreamCRTE = $this->generateNewCrossReferenceTableEntry();
            $crossReferenceStream = PdfStream::fromCrossReferenceTableEntry($crossReferenceStreamCRTE, $this);
            $crossReferenceStreamCRTE->setByteOffset($currentBytePos);
            /** @var PdfDictionary $crossReferenceStreamDictionary */
            $crossReferenceStreamDictionary = $crossReferenceStream->getContainingObject();
            $crossReferenceStreamDictionary->setObject("Type", new PdfName("XRef"));
            $indexArray = new PdfArray([]);

            // Berechne Breite der W-Einträge
            $typeByteSize = 1;
            $field2ByteSize = 0;
            $field3ByteSize = 0;
            foreach ($this->crossReferenceTable->getAll() as $crossReferenceTableEntry) {
                if ($crossReferenceTableEntry instanceof CompressedObjectCrossReferenceTableEntry) {
                    $field2ByteSize = max($field2ByteSize, $this->getNeededBytes($crossReferenceTableEntry->getObjectStreamNumber()));
                    $field3ByteSize = max($field3ByteSize, $this->getNeededBytes($crossReferenceTableEntry->getIndexInObjectStream()));
                } else {
                    $field2ByteSize = max($field2ByteSize, $this->getNeededBytes($crossReferenceTableEntry->getByteOffset()));
                    $field3ByteSize = max($field3ByteSize, $this->getNeededBytes($crossReferenceTableEntry->getGenerationNumber()));
                }
            }
            $crossReferenceStreamDictionary->setObject("W", new PdfArray([new PdfNumber($typeByteSize), new PdfNumber($field2ByteSize), new PdfNumber($field3ByteSize)]));
            // PNG-Predictor funktioniert, führt aber zu keiner Verbesserung
            // $crossReferenceStreamDictionary->setObject("DecodeParms", new PdfDictionary(["Predictor" => new PdfNumber(12), "Columns" => new PdfNumber($typeByteSize + $field2ByteSize + $field3ByteSize)]));

            // Schreibe alle CrossReferenceTableEntries in den CrossReferenceStream
            $entriesCount = count($this->crossReferenceTable->getAll());
            $string = "";
            $sectionStartEntry = 0;
            $writtenEntries = 0;
            while ($writtenEntries < $entriesCount) {
                // überspringe nicht vorhandene Einträge
                while (!$this->crossReferenceTable->hasEntry($sectionStartEntry))
                    ++$sectionStartEntry;

                // zähle Einträge, die nacheinander sind
                $sectionEndEntry = $sectionStartEntry;
                while ($this->crossReferenceTable->hasEntry($sectionEndEntry))
                    ++$sectionEndEntry;

                // schreibe die Sektion
                $indexArray->addObject(new PdfNumber($sectionStartEntry));
                $indexArray->addObject(new PdfNumber($sectionEndEntry - $sectionStartEntry));
                for ($i = $sectionStartEntry; $i < $sectionEndEntry; ++$i) {
                    $crossReferenceTableEntry = $this->crossReferenceTable->getEntry($i);
                    if ($crossReferenceTableEntry instanceof CompressedObjectCrossReferenceTableEntry) {
                        $string .= $this->intToString(2, $typeByteSize)
                            . $this->intToString($crossReferenceTableEntry->getObjectStreamNumber(), $field2ByteSize)
                            . $this->intToString($crossReferenceTableEntry->getIndexInObjectStream(), $field3ByteSize);
                    } else if ($crossReferenceTableEntry->isInUse()) {
                        $string .= $this->intToString(1, $typeByteSize)
                            . $this->intToString($crossReferenceTableEntry->getByteOffset(), $field2ByteSize)
                            . $this->intToString($crossReferenceTableEntry->getGenerationNumber(), $field3ByteSize);
                    } else {
                        $string .= $this->intToString(0, $typeByteSize)
                            . $this->intToString(0, $field2ByteSize)
                            . $this->intToString($crossReferenceTableEntry->getGenerationNumber(), $field3ByteSize);
                    }
                }

                // bereite nächste Sektion vor
                $writtenEntries += $sectionEndEntry - $sectionStartEntry;
                $sectionStartEntry = $sectionEndEntry;
            }
            $crossReferenceStream->setDecompressedStream($string);
            $crossReferenceStreamDictionary->setObject("Index", $indexArray);

            // Speichere CrossReferenceStream
            $crossReferenceStreamDictionary->merge($this->trailerDictionary);
            $fileContent .= $crossReferenceStream->toString();

            // Trailer
            $fileContent .= "startxref\n"
                . (string)$currentBytePos . "\n"
                . "%%EOF\n";

        } else {
            // CrossReferenceTable
            // $currentBytePos zeigt auf Start der CrossReferenceTable
            $fileContent .= $this->crossReferenceTable->toString();

            // Trailer
            $fileContent .= "trailer\n"
                . $this->trailerDictionary->toString() . "\n"
                . "startxref\n"
                . (string)$currentBytePos . "\n"
                . "%%EOF\n";
        }

        return $fileContent;
    }

    /**
     * Entfernt alle Objekte, die nicht von anderen Objekten referenziert werden, aus der Cross Reference Table
     */
    public function removeUnusedObjects()
    {
        // Setze ein Array mit used-Einträgen auf false
        $entries = $this->crossReferenceTable->getAll();
        $used = [];
        foreach ($entries as $objectNumber => $value) {
            $used[$objectNumber] = false;
        }
        $used[0] = true; // Eintrag Nummer 0 muss existieren.

        // Laufe alle Referenzen durch
        $this->findIndirectReferences($this->trailerDictionary, $used);

        // Entferne nicht genutzte Objekte
        foreach ($used as $objectNumber => $objectUsed) {
            if (!$objectUsed) {
                $this->crossReferenceTable->removeEntry($objectNumber);
                //echo "Removed Object $objectNumber\n";
            }
        }
    }

    /**
     * Läuft alle genutzten Objekte inklusive Referenzen durch und setzt den Eintrag für genutzte IndirectObjects in usedEntries auf true
     * @param PdfAbstractObject $object
     * @param bool[] $usedEntries
     */
    private function findIndirectReferences(PdfAbstractObject $object, array &$usedEntries)
    {
        if ($object instanceof PdfDictionary || $object instanceof PdfArray) {
            foreach ($object->getValue() as $entry)
                $this->findIndirectReferences($entry, $usedEntries);

        } else if ($object instanceof PdfIndirectReference) {
            if ($usedEntries[$object->getObjectNumber()] === false) {
                $usedEntries[$object->getObjectNumber()] = true;
                $this->findIndirectReferences($this->parseReference($object), $usedEntries);
            }
        }
    }


    /**
     * Liefert die für die gesamte PDF-Datei gültige CrossReferenceTable
     * @return crossReference\CrossReferenceTable
     */
    public function getCrossReferenceTable()
    {
        return $this->crossReferenceTable;
    }

    /**
     * Liefert einen Eintrag aus der CrossReferenceTable.
     * Ist dieser nicht vorhanden / Generierungsnummer falsch, wird null zurückgeliefert.
     * @param int $objectNumber Nummer des Indirect Object
     * @param int $generationNumber Generierungsnummer des Indirect Object
     * @return crossReference\CrossReferenceTableEntry|null
     */
    public function getCrossReferenceTableEntry(int $objectNumber, int $generationNumber)
    {
        $entry = $this->crossReferenceTable->getEntry($objectNumber);
        if ($entry !== null && $entry->getGenerationNumber() !== $generationNumber)
            return null;
        return $entry;
    }

    /**
     * Erstellt und liefert einen neuen Eintrag in der CrossReferenceTable
     * @return CrossReferenceTableEntry
     */
    public function generateNewCrossReferenceTableEntry(): CrossReferenceTableEntry
    {
        // Neue Nummer am "Ende" der CrossReferenceTable
        $objectNumber = $this->trailerDictionary->getObject("Size")->getValue();
        $this->trailerDictionary->setObject("Size", new PdfNumber($objectNumber + 1));

        $entry = new CrossReferenceTableEntry($objectNumber, 0, false, 0);
        $this->crossReferenceTable->setEntry($entry);
        return $entry;
    }

    /**
     * Liefert das Dictionary im Trailer.
     * @return object\PdfDictionary
     */
    public function getTrailerDictionary()
    {
        return $this->trailerDictionary;
    }

    /**
     * Liefert den Inhalt der PDF-Datei
     * @return misc\StringReader
     */
    public function getOriginalFileContent()
    {
        return $this->originalFileContent;
    }

    /**
     * Liefert die Anzahl der Bytes in der PDF-Datei
     * @return int
     */
    public function getOriginalFileContentLength()
    {
        return $this->originalFileContent->getStringLength();
    }

    /**
     * Liefert die PDF-Version (Wert zwischen 1.0 und 1.7)
     * @return string Version dieser PDF-Datei
     */
    public function getVersion(): string
    {
        return $this->fileVersion;
    }

    /**
     * Setze die PDF-Version (Wert zwischen 1.0 und 1.7)
     * @param string $versionString Version dieser PDF-Datei
     * @throws \Exception Wenn diese PDF-Version nicht unterstützt wird
     */
    public function setVersion(string $versionString)
    {
        if (strlen($versionString) !== 3 || $versionString[1] !== ".")
            throw new \Exception("Tried to set PDF Version to {$versionString}, this is not a valid PDF Version");

        $version = (int)$versionString[0];
        $subversion = (int)$versionString[2];

        if ($version != 1 || $subversion < 0 || $subversion > 7)
            throw  new \Exception("Version {$versionString} of the PDF standard is not supported");

        $this->fileVersion = $versionString;
    }

    /**
     * Setze die minimale PDF-Version (Wert zwischen 1.0 und 1.7).
     * Ist die Version höher, wird der Wert nicht verändert.
     * @param string $versionString Version dieser PDF-Datei
     * @throws \Exception Wenn diese PDF-Version nicht unterstützt wird
     */
    public function setMinVersion(string $versionString) {
        if ($this->fileVersion < $versionString)
            $this->setVersion($versionString);
    }


    /**
     * Löst eine Indirect Reference zu einem PdfAbstractObject auf.
     * Wenn das referenzierte IndirectObject nicht existiert, wird ein PdfNull zurückgeliefert.
     * Wenn das Objekt keine PdfIndirectReference ist, wird das Objekt direkt zurückgeliefert.
     * @param PdfAbstractObject|null $pdfObject Indirect Reference, die zu einem Objekt aufgelöst werden soll. Ist es keine PdfIndirectReference, wird es direkt zurückgeliefert.
     * @return PdfAbstractObject|null
     */
    public function parseReference(?PdfAbstractObject $pdfObject): ?PdfAbstractObject
    {
        if ($pdfObject == null || !($pdfObject instanceof PdfIndirectReference))
            return $pdfObject;

        $indirectObject = $this->getIndirectObject($pdfObject);
        if ($indirectObject === null)
            return new PdfNull(); // wenn kein Objekt existiert, soll das referenzierte Objekt als null angenommen werden

        return $indirectObject->getContainingObject();
    }

    /**
     * Liefert das Indirect Object passend zu einer Indirect Reference zurück.
     * @param PdfIndirectReference $reference Indirect Reference, zu welcher das IndirectObject gesucht werden soll.
     * @return PdfIndirectObject
     */
    public function getIndirectObject(PdfIndirectReference $reference): ?PdfIndirectObject
    {
        $crossReferenceTableEntry = $this->getCrossReferenceTableEntry($reference->getObjectNumber(), $reference->getGenerationNumber());
        if ($crossReferenceTableEntry == null)
            return null;
        return $crossReferenceTableEntry->getReferencedObject();
    }
}