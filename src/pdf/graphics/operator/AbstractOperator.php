<?php
namespace pdf\graphics\operator;

use pdf\graphics\state\GraphicsState;

/**
 * Abstrakte Klasse für einen Operatoren, der in einem ContentStream vorkommt
 * @package pdf\graphics\operator
 */
abstract class AbstractOperator
{
    /**
     * Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird.
     * @var OperatorMetadata|null
     */
    protected $operatorMetadata;

    /**
     * AbstractOperator constructor.
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(OperatorMetadata $operatorMetadata = null)
    {
        $this->operatorMetadata = $operatorMetadata;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    abstract function getOperator() : string ;

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt.
     * Dieser Beinhaltet auch einen EOL-Marker am Ende des Operatoren
     * @return string
     */
    abstract function __toString() : string ;

    /**
     * Liefert zurück, ob der AbstractOperator Einfluss auf den GraphicsState hat.
     * Standardwert: false
     * @return bool
     */
    function isGraphicsStateOperator() : bool {
        return false;
    }

    /**
     * Liefert zurück, ob der AbstractOperator etwas zeichnet.
     * Standardwert: false
     * @return bool
     */
    public function isRenderingOperator() : bool {
        return false;
    }

    /**
     * Für den Operatoren gültiger GraphicsState.
     * @return GraphicsState
     * @throws \Exception Wenn keine Metadaten im Konstruktor angegeben wurden
     */
    public function getGraphicsState(): GraphicsState
    {
        if ($this->operatorMetadata === null)
            throw new \Exception("No Metadata available for this Operator");
        return $this->operatorMetadata->graphicsState;
    }

    /**
     * Der wievielte Operator im analysierten ContentStream dies ist
     * @return int
     * @throws \Exception Wenn keine Metadaten im Konstruktor angegeben wurden
     */
    public function getOperatorNumber(): int
    {
        if ($this->operatorMetadata === null)
            throw new \Exception("No Metadata available for this Operator");
        return $this->operatorMetadata->operatorNumber;
    }

    /**
     * An welcher BytePosition im ContentStream der Operator beginnt.
     * @return int
     * @throws \Exception Wenn keine Metadaten im Konstruktor angegeben wurden
     */
    public function getBytePositionInStream(): ?int
    {
        if ($this->operatorMetadata === null)
            throw new \Exception("No Metadata available for this Operator");
        return $this->operatorMetadata->bytePositionInStream;
    }

    /**
     * Setzt die BytePosition, an welcher im ContentStream der Operator beginnt.
     * @param int $bytePosition Byteposition im ContentStream, an der der Operator beginnt
     * @throws \Exception Wenn keine Metadaten im Konstruktor angegeben wurden
     */
    public function setBytePositionInStream(int $bytePosition) {
        if ($this->operatorMetadata === null)
            throw new \Exception("No Metadata available for this Operator");
        $this->operatorMetadata->bytePositionInStream = $bytePosition;
    }
}