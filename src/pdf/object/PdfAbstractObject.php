<?php

namespace pdf\object;

/**
 * Abstrakte Repräsentation eines PDF-Objektes, ausgenommen indirekter Objekte
 * @package pdf\object
 */
abstract class PdfAbstractObject
{
    /**
     * Ob vor dem Objekt ein "white space" benötigt wird, weil das Objekt nicht mit einem Trennzeichen beginnt.
     * @return bool
     * @see PdfAbstractObject::needsWhiteSpaceAfter() Ein Trennzeichen wird nur benötigt, wenn beim vorherigen Objekt ebenfalls ein Trennzeichen benötigt wird.
     */
    public abstract function needsWhiteSpaceBefore(): bool;

    /**
     * Ob nach dem Objekt ein "white space" benötigt wird, weil das Objekt nicht mit einem Trennzeichen endet.
     * @return bool
     * @see PdfAbstractObject::needsWhiteSpaceBefore() Ein Trennzeichen wird nur benötigt, wenn beim nachfolgenden Objekt ebenfalls ein Trennzeichen benötigt wird.
     */
    public abstract function needsWhiteSpaceAfter(): bool;

    /**
     * Liefert den Wert dieses Objektes zurück.
     * Manche Objekte liefern darüber hinaus bessere Methoden, um den Objektinhalt abzufragen.
     * @return mixed
     */
    public abstract function getValue();

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei
     * @return string
     */
    public abstract function toString(): string;

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer des ObjectParsers genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @return PdfAbstractObject ein neues Objekt
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     */
    public static abstract function parse(ObjectParser $objectParser): PdfAbstractObject;

    /**
     * Liefert zurück, ob das übergebene Objekt diesem hier gleicht
     * @param mixed $another Anderes Objekt zum Vergleichen
     * @return bool
     */
    public function equals($another): bool
    {
        if (get_class($this) !== get_class($another))
            return false;

        return $this->getValue() === $another->getValue();
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfAbstractObject
     */
    public abstract function clone(): PdfAbstractObject;
}