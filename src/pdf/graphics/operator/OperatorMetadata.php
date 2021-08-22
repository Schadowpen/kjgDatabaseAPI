<?php
namespace pdf\graphics\operator;

use pdf\graphics\state\GraphicsState;

/**
 * Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird.
 * @package pdf\graphics\operator
 */
class OperatorMetadata
{

    /**
     * Für den Operatoren gültiger GraphicsState.
     * @var GraphicsState
     */
    public $graphicsState;
    /**
     * Der wievielte Operator im analysierten ContentStream dies ist
     * @var int
     */
    public $operatorNumber;
    /**
     * An welcher BytePosition im ContentStream der Operator beginnt.
     * @var int
     */
    public $bytePositionInStream;

    /**
     * AbstractOperator constructor.
     * @param GraphicsState $graphicsState Für den Operatoren gültiger GraphicsState
     * @param int $operatorNumber Der wievielte Operator im analysierten ContentStream dies ist
     * @param int $bytePositionInStream An welcher BytePosition im ContentStream der Operator beginnt.
     */
    public function __construct(GraphicsState $graphicsState, int $operatorNumber, int $bytePositionInStream)
    {
        $this->graphicsState = $graphicsState;
        $this->operatorNumber = $operatorNumber;
        $this->bytePositionInStream = $bytePositionInStream;
    }
}