<?php
namespace pdf\graphics\operator;

use pdf\graphics\TransformationMatrix;

/**
 * Operator, welcher die angegebene Transformationsmatrix mit der bestehenden Transformationsmatrix konkateniert.
 * @package pdf\graphics\operator
 */
class ModifyTransformationMatrixOperator extends AbstractOperator
{
    /**
     * Die in dem Operatoren definierte Transformationsmatrix
     * @var TransformationMatrix
     */
    protected $transformationMatrix;

    /**
     * ModifyTransformationMatrixOperator constructor.
     * @param TransformationMatrix $transformationMatrix
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(TransformationMatrix $transformationMatrix, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->transformationMatrix = $transformationMatrix;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "cm";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return sprintf("%f", $this->transformationMatrix->getA()) . " "
            . sprintf("%f", $this->transformationMatrix->getB()) . " "
            . sprintf("%f", $this->transformationMatrix->getC()) . " "
            . sprintf("%f", $this->transformationMatrix->getD()) . " "
            . sprintf("%f", $this->transformationMatrix->getE()) . " "
            . sprintf("%f", $this->transformationMatrix->getF()) . " cm\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * Gibt die in dem Operatoren definierte Transformationsmatrix zurück
     * @return TransformationMatrix
     */
    public function getTransformationMatrix(): TransformationMatrix
    {
        return $this->transformationMatrix;
    }
}