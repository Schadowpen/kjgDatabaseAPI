<?php


namespace pdf\graphics\operator;

use pdf\graphics\Point;
use pdf\object\PdfNumber;

/**
 * Operator zum hinzufügen eines Rechteckes zum aktuellen Pfad
 * @package pdf\graphics\operator
 */
class PathRectangleOperator extends PathConstructionOperator
{
    /**
     * X-Position des Punktes links unten in User Space
     * @var PdfNumber
     */
    protected $x;
    /**
     * Y-Position des Punktes links unten in User Space
     * @var PdfNumber
     */
    protected $y;
    /**
     * Breite des Rechteckes in User Space
     * @var PdfNumber
     */
    protected $width;
    /**
     * Höhe des Rechteckes in User Space
     * @var PdfNumber
     */
    protected $height;

    /**
     * PathRectangleOperator constructor.
     * @param PdfNumber $x X-Position des Punktes links unten in User Space
     * @param PdfNumber $y Y-Position des Punktes links unten in User Space
     * @param PdfNumber $width Breite des Rechteckes in User Space
     * @param PdfNumber $height Höhe des Rechteckes in User Space
     * @param OperatorMetadata $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $x, PdfNumber $y, PdfNumber $width, PdfNumber $height, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }


    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "re";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->x->toString() . " "
            . $this->y->toString() . " "
            . $this->width->toString() . " "
            . $this->height->toString() . " re\n";
    }

    /**
     * Liefert den letzten Punkt des Operators, der dann als Startpunkt für den nächsten Operatoren genutzt wird.
     * Sollte der Operator einen Unterpfad beenden, wird null zurückgeliefert
     * @return null|Point
     */
    public function getLastPoint(): ?Point
    {
        return null;
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getX(): PdfNumber
    {
        return $this->x;
    }

    /**
     * @return PdfNumber
     */
    public function getY(): PdfNumber
    {
        return $this->y;
    }

    /**
     * @return PdfNumber
     */
    public function getWidth(): PdfNumber
    {
        return $this->width;
    }

    /**
     * @return PdfNumber
     */
    public function getHeight(): PdfNumber
    {
        return $this->height;
    }
}