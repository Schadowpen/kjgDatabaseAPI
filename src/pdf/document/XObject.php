<?php


namespace pdf\document;


use pdf\graphics\Point;
use pdf\object\PdfIndirectReference;
use pdf\PdfFile;

/**
 * Abstrakte Klasse für ein XObjekt, kann entweder ein Image oder ein Form XObjekt sein.
 * @package pdf\document
 */
abstract class XObject extends AbstractDocumentStream
{

    public static function objectType(): ?string
    {
        return "XObject";
    }

    /**
     * Parst eine Indirect Reference in eine XObject-Instanz.
     * Dabei wird die jeweilige Unterklasse zum XObject erzeugt, die dem im Dictionary angegebenen Type entspricht.
     * @param PdfIndirectReference $reference Referenz auf das Indirect Object
     * @param PdfFile $pdfFile PdfFile, in welcher sich das IndirectObject befindet
     * @return XObject
     * @throws \Exception Wenn das Externe Objekt nicht unterstützt wird.
     */
    public static function parseReference(PdfIndirectReference $reference, PdfFile $pdfFile) {
        $indirectObject = $pdfFile->getIndirectObject($reference);
        $dictionary = $indirectObject->getContainingObject();
        if (XObjectImage::matchesType($dictionary))
            return new XObjectImage($indirectObject, $pdfFile);
        if (XObjectForm::matchesType($dictionary))
            return new XObjectForm($indirectObject, $pdfFile);
        throw new \Exception("Unsupported external Object Type in {$reference->toString()}");
    }

    /**
     * Liefert die Ecke links unten vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public abstract function getLowerLeftCorner() : Point;

    /**
     * Liefert die Ecke rechts unten vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public abstract function getLowerRightCorner() : Point;

    /**
     * Liefert die Ecke links oben vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public abstract function getUpperLeftCorner() : Point;

    /**
     * Liefert die Ecke rechts oben vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public abstract function getUpperRightCorner() : Point;
}