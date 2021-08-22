<?php

namespace pdf\object;

/**
 * Ein PDF-Objekt, welches ein Array beinhaltet
 * @package pdf\object
 */
class PdfArray extends PdfAbstractObject
{
    /**
     * Array an Pdf-Objekten, die in diesem PdfArray sind
     * @var PdfAbstractObject[]
     */
    private $array;

    /**
     * PdfArray constructor.
     * @param array $array Array an Pdf-Objekten, die in diesem PdfArray sind
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * Ob vor dem Objekt ein "white space" benötigt wird, weil das Objekt nicht mit einem Trennzeichen beginnt.
     * @return bool
     * @see PdfAbstractObject::needsWhiteSpaceAfter() Ein Trennzeichen wird nur benötigt, wenn beim vorherigen Objekt ebenfalls ein Trennzeichen benötigt wird.
     */
    public function needsWhiteSpaceBefore(): bool
    {
        return false;
    }

    /**
     * Ob nach dem Objekt ein "white space" benötigt wird, weil das Objekt nicht mit einem Trennzeichen endet.
     * @return bool
     * @see PdfAbstractObject::needsWhiteSpaceBefore() Ein Trennzeichen wird nur benötigt, wenn beim nachfolgenden Objekt ebenfalls ein Trennzeichen benötigt wird.
     */
    public function needsWhiteSpaceAfter(): bool
    {
        return false;
    }

    /**
     * Liefert den Inhalt dieses Objektes als Array zurück.
     * @return PdfAbstractObject[]
     * @see PdfArray::getObject() um gezielt Objekte abzufragen
     */
    public function getValue()
    {
        return $this->array;
    }

    /**
     * Liefert das Objekt an der angegebenen Arrayposition.
     * Wenn die Position nicht in dem Array vorkommt, wird null zurückgeliefert
     * @param int $index Index in dem Array
     * @return PdfAbstractObject|null
     */
    public function getObject(int $index) {
        return @$this->array[$index];
    }

    /**
     * Ersetzt das Objekt an der angegebenen Arrayposition
     * @param int $index Position im Array, welches Objekt ersetzt werden soll
     * @param PdfAbstractObject $object Neues Objekt
     * @throws \Exception Wenn der Index außerhalb des Arrays ist
     */
    public function setObject(int $index, PdfAbstractObject $object) {
        if ($index < 0 || $index >= $this->getArraySize())
            throw new \Exception("Index {$index} out of Range of PdfArray with size " . $this->getArraySize());
        $this->array[$index] = $object;
    }

    /**
     * Fügt das Objekt am Ende des Arrays an
     * @param PdfAbstractObject $object Anzufügendes Objekt
     */
    public function addObject(PdfAbstractObject $object) {
        $this->array[] = $object;
    }

    /**
     * Entfernt das Objekt an der Stelle index aus dem Array
     * @param int $index Position im Array, welches Objekt gelöscht werden soll.
     */
    public function removeObject(int $index) {
        array_splice($this->array, $index, 1);
    }

    /**
     * Liefert die Größe des Arrays
     * @return int
     */
    public function getArraySize() {
        return count($this->array);
    }

    /**
     * Erstellt für dieses Objekt einen String zum einbetten in eine PDF-Datei
     * @return string
     */
    public function toString(): string
    {
        $arraySize = $this->getArraySize();
        if ($arraySize === 0) {
            return "[]";
        } else {
            $string = "[" . $this->array[0]->toString();
            for ($i = 1; $i < $arraySize; ++$i) {
                if ($this->array[$i-1]->needsWhiteSpaceAfter() && $this->array[$i]->needsWhiteSpaceBefore())
                    $string .= " ";
                $string .= $this->array[$i]->toString();
            }
            return $string . "]";
        }
    }

    /**
     * Wenn der ObjectParser ein bestimmtes Objekt anhand des letzten Tokens erkannt hat, kann mit dieser Funktion das Objekt erzeugt werden.
     * Es wird angenommen, dass die Delimiter am Anfang des Objektes bereits vom Tokenizer des ObjectParsers genutzt wurden, der Inhalt und die Delimiter am Ende jedoch nicht.
     * @param ObjectParser $objectParser ObjectParser, welcher dieses Objekt erkannt hat
     * @return PdfArray ein neues Objekt
     * @throws \Exception Wenn beim Parsen ein Fehler auftritt
     */
    public static function parse(ObjectParser $objectParser): PdfAbstractObject
    {
        $array = [];
        $pdfAbstractObject = $objectParser->parseObject(true);
        while (!($pdfAbstractObject instanceof PdfToken && $pdfAbstractObject->getValue() === "]")) {
            if ($pdfAbstractObject instanceof PdfToken)
                throw new \Exception("No plain PdfToken allowed inside a PdfArray");
            $array[] = $pdfAbstractObject;
            $pdfAbstractObject = $objectParser->parseObject(true);
        }
        return new PdfArray($array);
    }


    public function equals($another): bool
    {
        if (self::class !== get_class($another))
            return false;

        // Überprüfe Menge der Objekte
        $size = $this->getArraySize();
        if ($size != $another->getArraySize())
            return false;

        // Für jedes Objekt, verlgeiche Wert
        for ($i = 0; $i < $size; ++$i) {
            if (!$this->getObject($i)->equals($another->getObject($i)))
                return false;
        }

        return true;
    }

    /**
     * Erzeugt eine (tiefe) Kopie dieses Objektes
     * @return PdfArray
     */
    public function clone(): PdfAbstractObject
    {
        $arraySize = $this->getArraySize();
        $newArray = [];
        for ($i = 0; $i < $arraySize; ++$i) {
            $newArray[$i] = $this->array[$i]->clone();
        }
        return new PdfArray($newArray);
    }
}