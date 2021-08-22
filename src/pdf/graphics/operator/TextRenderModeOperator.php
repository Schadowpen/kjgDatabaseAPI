<?php


namespace pdf\graphics\operator;

use pdf\object\PdfNumber;

/**
 * Operator zum setzen des Modus, wie Text gerendert wird.
 * @package pdf\graphics\operator
 */
class TextRenderModeOperator extends AbstractOperator
{
    public const fillText = 0;
    public const strokeText = 1;
    public const fillAndStrokeText = 2;
    public const invisibleText  = 3;
    public const fillTextAndAddToPathForClipping = 4;
    public const strokeTextAndAddToPathForClipping = 5;
    public const fillAndStrokeTextAndAddToPathForClipping = 6;
    public const addTextToPathForClipping = 7;

    /**
     * Modus, in welchem Text gerendert werden soll.
     * @var PdfNumber
     */
    protected $renderMode;

    /**
     * TextRenderModeOperator constructor.
     * @param PdfNumber $renderMode Modus, in welchem Text gerendert werden soll
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $renderMode, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->renderMode = $renderMode;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Tr";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->renderMode->toString() . " Tr\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getRenderMode(): PdfNumber
    {
        return $this->renderMode;
    }
}