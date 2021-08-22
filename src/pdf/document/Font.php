<?php


namespace pdf\document;


use pdf\indirectObject\PdfIndirectObject;
use pdf\object\PdfDictionary;
use pdf\object\PdfIndirectReference;
use pdf\PdfFile;

/**
 * Eine Schriftart, die beschreibt, wie Text in einer PDF Dargestellt wird
 * @package pdf\document
 */
abstract class Font extends AbstractDocumentObject
{
    /**
     * Erzeugt eine Unterklasse von Font, abhängig davon welcher Subtype gegeben ist.
     * @param $pdfObject PdfIndirectObject|PdfIndirectObject|PdfDictionary Entweder ein Dictionary mit dem Objekt ODER ein IndirectObject, welches dieses Dictionary beinhaltet ODER eine Referenz auf das IndirectObject
     * @param PdfFile $pdfFile PDF-Datei, in welcher das Objekt liegt.
     * @return Font Unterklasse von Font
     * @throws \Exception Wenn diese Font nicht bekannt ist
     */
    public static function getFont($pdfObject, PdfFile $pdfFile): Font
    {
        if ($pdfObject instanceof PdfIndirectReference)
            $pdfObject = $pdfFile->getIndirectObject($pdfObject);

        if ($pdfObject instanceof PdfIndirectObject)
            $dictionary = $pdfObject->getContainingObject();
        else
            $dictionary =& $pdfObject;

        if (FontType0::matchesType($dictionary))
            return new FontType0($pdfObject, $pdfFile);
        if (FontType1::matchesType($dictionary))
            return new FontType1($pdfObject, $pdfFile);
        if (FontMultipleMaster::matchesType($dictionary))
            return new FontMultipleMaster($pdfObject, $pdfFile);
        if (FontType3::matchesType($dictionary))
            return new FontType3($pdfObject, $pdfFile);
        if (FontTrueType::matchesType($dictionary))
            return new FontTrueType($pdfObject, $pdfFile);

        throw new \Exception("Font Type could not be indicated");
    }

    /**
     * Erzeugt eine der 14 Standard Schriftarten
     * @param string $fontBaseName Name einer der 14 Standard-Schriftarten
     * @param PdfFile $pdfFile PDF-Datei, in welcher das Objekt liegt.
     * @return FontType1 Font-Objekt mit einer der Standard 14 Fonts
     * @throws \Exception Wenn der übergebene Font BaseName keine der Standard 14 Fonts ist.
     */
    public static function getStandard14Font(string $fontBaseName, PdfFile $pdfFile) : FontType1 {
        switch ($fontBaseName) {
            case "Courier":
                return FontType1::Courier($pdfFile);
            case "Courier-Bold":
                return FontType1::CourierBold($pdfFile);
            case "Courier-Oblique":
                return FontType1::CourierItalic($pdfFile);
            case "Courier-BoldOblique":
                return FontType1::CourierBoldItalic($pdfFile);
            case "Helvetica":
                return FontType1::Helvetica($pdfFile);
            case "Helvetica-Bold":
                return FontType1::HelveticaBold($pdfFile);
            case "Helvetica-Oblique":
                return FontType1::HelveticaItalic($pdfFile);
            case "Helvetica-BoldOblique":
                return FontType1::HelveticaBoldItalic($pdfFile);
            case "Symbol":
                return FontType1::Symbol($pdfFile);
            case "Times-Roman":
                return FontType1::TimesNewRoman($pdfFile);
            case "Times-Bold":
                return FontType1::TimesNewRomanBold($pdfFile);
            case "Times-Italic":
                return FontType1::TimesNewRomanItalic($pdfFile);
            case "Times-BoldItalic":
                return FontType1::TimesNewRomanBoldItalic($pdfFile);
            case "ZapfDingbats":
                return FontType1::ZapfDingbats($pdfFile);
            default:
                throw new \Exception("Font is not one of the Standard 14 Fonts");
        }
    }

    /**
     * Liefert den Type dieser Klasse an Dokument Objekten.
     * Er sollte in einem zugehörigen Dictionary immer unter Type enthalten sein.
     * <br/>
     * Sollte für die Objektklasse kein Type-Attribut benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static function objectType(): ?string
    {
        return "Font";
    }

    /**
     * Liefert den Namen der Schriftart
     * @return string
     */
    public abstract function getBaseFontName(): string;

    /**
     * Liefert die Breite eines einzelnen Zeichens
     * @param int $charCode Zeichencode des Zeichens, dessen Breite zu ermitteln ist
     * @return float Breite in Glyph Space
     */
    public abstract function getCharWidth(int $charCode);

    /**
     * Kodiert einen UTF-8 String in einen entsprechend dem Font kodierten String.
     * @param string $utf8String Mit UTF-8 Kodierter Text
     * @return string Entsprechend dem Font kodierter Text
     * @throws \Exception Wenn die Kodierung im Font nicht unterstützt wird.
     */
    public abstract function fromUTF8(string $utf8String) : string;

    /**
     * Kodiert einen entsprechend dem Font kodierten String in einen UTF-8 String.
     * Sollte dies nicht möglich sein, etwa weil der Zeichensatz nicht unterstützt wird, wird zumindest der originale String zurückgeliefert.
     * @param string $fontEncodedString Entsprechend dem Font kodierter Text
     * @return string UTF-8 kodierter Text oder $fontEncodedString
     */
    public abstract function toUTF8(string $fontEncodedString) : string ;
}