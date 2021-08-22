<?php


namespace pdf\graphics\operator;


use pdf\object\PdfName;

/**
 * Operator zum setzen des Rendering Intents.
 * Dieser gibt an, wie Farben modifiziert werden sollen, wenn sie nicht wie angegeben angezeigt werden können.
 * Mögliche Rendering Intents:<br/><b>
 * AbsoluteColorimetric <br/>
 * RelativeColorimetric <br/>
 * Saturation <br/>
 * Perceptual <br/></b>
 * @package pdf\graphics\operator
 */
class ColorRenderingIntentOperator extends AbstractOperator
{
    /**
     * Wie Farben transformiert werden sollen, wenn sie nicht exakt dargestellt werden können.
     * @var PdfName
     */
    protected $renderingIntent;

    /**
     * ColorRenderingIntentOperator constructor.
     * @param PdfName $renderingIntent Wie Farben transformiert werden sollen, wenn sie nicht exakt dargestellt werden können.
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfName $renderingIntent, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->renderingIntent = $renderingIntent;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "ri";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->renderingIntent->toString() . " ri\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfName
     */
    public function getRenderingIntent(): PdfName
    {
        return $this->renderingIntent;
    }
}