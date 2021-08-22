<?php

namespace pdf\indirectObject;

use pdf\object\ObjectParser;
use pdf\PdfFile;

/**
 * Parser zum Einlesen von Indirect Objects und Streams (welche Indirect Objects sind)
 * @package pdf\indirectObject
 */
class IndirectObjectParser
{
    /**
     * Liest ein Indirect Object / Stream ein.
     * @param PdfFile $pdfFile PdfFile, in wessen Originalinhalt das Objekt zu finden ist
     * @param int $objectStartPos Byteposition, an welcher das Indirect Object beginnt
     * @return PdfIndirectObject
     * @throws \Exception Wenn das Indirect Object nicht gefunden werden kann
     */
    public static function parse(PdfFile $pdfFile, int $objectStartPos): PdfIndirectObject {
        $objectParser = new ObjectParser($pdfFile->getOriginalFileContent(), $objectStartPos);

        // lese erste Zeile
        $objectNumber = (int) $objectParser->getTokenizer()->getToken();
        $generationNumber = (int) $objectParser->getTokenizer()->getToken();
        if ($objectParser->getTokenizer()->getToken() !== "obj")
            throw new \Exception("Indirect Object not found by 'obj' Identifier");

        // lese Inhalt
        $content = $objectParser->parseObject();

        // Erkenne ob Ende oder Stream
        $token = $objectParser->getTokenizer()->getToken();
        if ($token === "endobj")
            return new PdfIndirectObject($objectNumber, $generationNumber, $content);

        if ($token === "stream") {
            $objectParser->getStringReader()->skipLine(); // Skippe bis zur nächsten Zeile, sodass Reader am Start des Streams steht
            return new ExistingPdfStream($objectNumber, $generationNumber, $content, $pdfFile, $objectParser->getStringReader()->getReaderPos());
        }

        throw new \Exception("Indirect Object not finished with 'endobj'");
    }
}