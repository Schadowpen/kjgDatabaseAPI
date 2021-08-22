<?php


namespace pdf\graphics\operator;

use pdf\object\PdfName;

/**
 * Operator zum direkten Zeichnen eines Shading Pattern in den aktuellen User Space. Dabei wird der aktuelle Clipping Path berücksichtigt.
 * @package pdf\graphics\operator
 */
class ShadingOperator extends AbstractOperator
{
    /**
     * Name des ShadingDictionarys im Resource Dictionary
     * @var PdfName
     */
    protected $shadingDictionaryName;

    public function __construct(PdfName $shadingDictionaryName, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->shadingDictionaryName = $shadingDictionaryName;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "sh";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->shadingDictionaryName->toString() . " sh\n";
    }

    public function isRenderingOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfName
     */
    public function getShadingDictionaryName(): PdfName
    {
        return $this->shadingDictionaryName;
    }
}