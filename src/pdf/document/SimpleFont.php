<?php


namespace pdf\document;

use pdf\object\PdfArray;
use pdf\object\PdfDictionary;

/**
 * Überklasse SimpleFont, implementiert die Funktionen für FontType1, FontMultipleMaster, FontType3 und FontTrueType, da diese überall gleich sind.
 * @package pdf\document
 */
abstract class SimpleFont extends Font
{

    public function getBaseFontName(): string
    {
        return $this->get("BaseFont")->getValue();
    }

    /**
     * Liefert die Breite eines einzelnen Zeichens
     * @param int $charCode Zeichencode des Zeichens, dessen Breite zu ermitteln ist
     * @return float Breite in Glyph Space
     */
    public function getCharWidth(int $charCode)
    {
        /** @var int $firstChar */
        $firstChar = $this->get("FirstChar")->getValue();
        /** @var int $lastChar */
        $lastChar = $this->get("LastChar")->getValue();
        // Wenn Außerhalb Widths Array:
        if ($charCode < $firstChar || $charCode > $lastChar) {
            /** @var PdfDictionary $fontDescriptor */
            $fontDescriptor = $this->get("FontDescriptor");
            $missingWidth = $fontDescriptor->getObject("MissingWidth");
            if ($missingWidth === null)
                return 0;
            else
                return $missingWidth->getValue();
        }

        /** @var PdfArray $widths */
        $widths = $this->get("Widths");
        return $widths->getObject($charCode - $firstChar)->getValue();
    }

    public function fromUTF8(string $utf8String): string
    {
        if (!extension_loaded("iconv"))
            throw new \Exception("iconv Extension not loaded");

        $encoding = $this->get("Encoding");
        if ($encoding === null)
            throw new \Exception("Built-In Font Encoding not supported");
        if ($encoding instanceof PdfDictionary)
            throw new \Exception("Encoding Dictionary not supported");
        switch ($encoding->getValue()) {
            case "WinAnsiEncoding":
                $result = iconv('UTF-8', 'CP1252//TRANSLIT', $utf8String);
                if ($result === false)
                    throw new \Exception("Could not encode String from UTF-8 to ANSI");
                return $result;

            case "MacRomanEncoding":
                $result = iconv('UTF-8', 'macintosh//TRANSLIT', $utf8String);
                if ($result === false)
                    throw new \Exception("Could not encode String from UTF-8 to MacRoman");
                return $result;

            default:
                throw new \Exception("Font Encoding not supported.");
        }
    }

    public function toUTF8(string $fontEncodedString): string
    {
        if (!extension_loaded("iconv"))
            return $fontEncodedString;

        switch ($this->get("Encoding")->getValue()) {
            case "WinAnsiEncoding":
                return iconv('CP1252', 'UTF-8', $fontEncodedString);
            case "MacRomanEncoding":
                return iconv('macintosh', 'UTF-8', $fontEncodedString);
            default:
                return $fontEncodedString;
        }
    }
}