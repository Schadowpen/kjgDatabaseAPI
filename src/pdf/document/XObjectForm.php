<?php


namespace pdf\document;

use pdf\graphics\Point;
use pdf\graphics\TransformationMatrix;
use pdf\indirectObject\PdfStream;
use pdf\object\PdfDictionary;

/**
 * Ein externes Objekt, welches ähnlich wie ein Content Stream Grafikbefehle beinhalten kann.
 * Es wird hier der Einfachheit halber wie ein Image behandelt.
 * @package pdf\document
 */
class XObjectForm extends XObject
{
    public static function objectSubtype(): ?string
    {
        return "Form";
    }

    public function getBBox() : PdfRectangle {
        return PdfRectangle::parsePdfArray($this->get("BBox"));
    }
    public function getMatrix() : TransformationMatrix {
        $matrix = $this->get("Matrix");
        if ($matrix === null)
            return new TransformationMatrix();
        return TransformationMatrix::parsePdfArray($matrix);
    }
    public function getResources() : ?ResourceDictionary {
        $value = $this->get("Resources");
        if ($value === null)
            return null;
        return new ResourceDictionary($value, $this->pdfFile);
    }

    /**
     * Liefert die Ecke links unten vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getLowerLeftCorner(): Point
    {
        return $this->getMatrix()->transformPoint($this->getBBox()->getLowerLeftPoint());
    }

    /**
     * Liefert die Ecke rechts unten vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getLowerRightCorner(): Point
    {
        return $this->getMatrix()->transformPoint($this->getBBox()->getLowerRightPoint());
    }

    /**
     * Liefert die Ecke links oben vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getUpperLeftCorner(): Point
    {
        return $this->getMatrix()->transformPoint($this->getBBox()->getUpperLeftPoint());
    }

    /**
     * Liefert die Ecke rechts oben vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getUpperRightCorner(): Point
    {
        return $this->getMatrix()->transformPoint($this->getBBox()->getUpperRightPoint());
    }
}