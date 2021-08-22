<?php


namespace pdf\graphics\operator;


use pdf\object\PdfNumber;

/**
 * Operator, welcher für gebogene Pfade die Toleranz zwischen gezeichneter Linie und mathematisch korrektem Pfad in Device Pixeln angibt.
 * @package pdf\graphics\operator
 */
class FlatnessOperator extends AbstractOperator
{
    /**
     * Maximalabstand zwischen gezeichnetem und mathematisch korrektem Pfad in Device Pixeln
     * @var PdfNumber
     */
    protected $flatness;

    /**
     * FlatnessOperator constructor.
     * @param PdfNumber $flatness Maximalabstand zwischen gezeichnetem und mathematisch korrektem Pfad in Device Pixeln
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $flatness, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->flatness = $flatness;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "i";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->flatness->toString() . " i\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getFlatness(): PdfNumber
    {
        return $this->flatness;
    }
}