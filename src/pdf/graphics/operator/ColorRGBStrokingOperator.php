<?php


namespace pdf\graphics\operator;


use pdf\graphics\ColorRGB;

/**
 * Operator zum Setzen der Farbe für Linien Zeichnen auf einen RGB-Wert
 * @package pdf\graphics\operator
 */
class ColorRGBStrokingOperator extends AbstractOperator
{
    /**
     * Neue Farbe für Linien zeichnen
     * @var ColorRGB
     */
    protected $color;

    /**
     * ColorRGBStrokingOperator constructor.
     * @param ColorRGB $color Neue Farbe für Linien zeichnen
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(ColorRGB $color, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->color = $color;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "RG";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->color->getRed() . " "
            . $this->color->getGreen() . " "
            . $this->color->getBlue() . " RG\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return ColorRGB
     */
    public function getColor(): ColorRGB
    {
        return $this->color;
    }
}