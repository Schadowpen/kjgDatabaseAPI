<?php


namespace pdf\document;

// Font Type 0 hat keine tatsächliche Implementierung für getCharWidth(), fromUTF8() und toUTF8(), da zu komplex

/**
 * Eine Schriftart vom Type 0.
 * Diese ist im Unterschied zu anderen Schriftarten sehr komplex, vor allem da Multibyte-Zeichensätze unterstützt werden.
 * Da sie im aktuellen Kontext nicht genutzt wird, wurden für die meisten Funktionen nur Dummyfunktionalitäten implementiert.
 *
 * @package pdf\document
 */
class FontType0 extends Font
{

    /**
     * Liefert den Subtype dieser Klasse an Dokument Objekten.
     * Sollte für die Dokumentklasse kein SubType benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static function objectSubtype(): ?string
    {
        return "Type0";
    }

    /**
     * Liefert den Namen der Schriftart
     * @return string
     */
    public function getBaseFontName(): string
    {
        return $this->get("BaseFont")->getValue();
    }

    /**
     * Liefert die Breite eines einzelnen Zeichens
     * <br/><b>
     * Diese Funktion wird nicht unterstützt, da deren Verhalten in FontType0 zu komplex ist.
     * Stattdessen wird eine Standardbreite von 1 im Glyph Space zurückgegeben, um Divisionen durch 0 zu vermeiden
     *
     * @param int $charCode Zeichencode des Zeichens, dessen Breite zu ermitteln ist
     * @return float Breite in Glyph Space
     */
    public function getCharWidth(int $charCode)
    {
        return 1;
    }

    /**
     * Kodiert einen UTF-8 String in einen entsprechend dem Font kodierten String.
     * <br/><b>
     * Diese Funktion wird nicht unterstützt, da deren Verhalten in FontType0 zu komplex ist.
     * Stattdessen wird auf jeden Fall eine Exception geschmissen
     *
     * @param string $utf8String Mit UTF-8 Kodierter Text
     * @return string Entsprechend dem Font kodierter Text
     * @throws \Exception Wenn die Kodierung im Font nicht unterstützt wird.
     */
    public function fromUTF8(string $utf8String): string
    {
        throw new \Exception("No Character Code Encoding for Fonts of Type0");
    }

    /**
     * Kodiert einen entsprechend dem Font kodierten String in einen UTF-8 String.
     * Sollte dies nicht möglich sein, etwa weil der Zeichensatz nicht unterstützt wird, wird zumindest der originale String zurückgeliefert.
     * <br/><b>
     * Diese Funktion wird nicht unterstützt, da deren Verhalten in FontType0 zu komplex ist.
     * Stattdessen wird der originale String zurückgeliefert
     *
     * @param string $fontEncodedString Entsprechend dem Font kodierter Text
     * @return string UTF-8 kodierter Text oder $fontEncodedString
     */
    public function toUTF8(string $fontEncodedString): string
    {
        return $fontEncodedString;
    }
}