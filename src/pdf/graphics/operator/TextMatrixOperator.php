<?php


namespace pdf\graphics\operator;

use pdf\graphics\TransformationMatrix;

/**
 * Operator zum Setzen der Text Matritzen im TextObjectState
 * @package pdf\graphics\operator
 */
class TextMatrixOperator extends AbstractOperator
{
    /**
     * Neu zu setzende TextMatrix und TextLineMatrix
     * @var TransformationMatrix
     */
    protected $textMatrix;

    /**
     * TextMatrixOperator constructor.
     * @param TransformationMatrix $textMatrix Neu zu setzende TextMatrix und TextLineMatrix
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(TransformationMatrix $textMatrix, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->textMatrix = $textMatrix;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Tm";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->textMatrix->getA() . " "
            . $this->textMatrix->getB() . " "
            . $this->textMatrix->getC() . " "
            . $this->textMatrix->getD() . " "
            . $this->textMatrix->getE() . " "
            . $this->textMatrix->getF() . " Tm\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return TransformationMatrix
     */
    public function getTextMatrix(): TransformationMatrix
    {
        return $this->textMatrix;
    }
}