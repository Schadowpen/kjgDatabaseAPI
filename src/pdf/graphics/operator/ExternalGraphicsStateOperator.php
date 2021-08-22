<?php


namespace pdf\graphics\operator;


use pdf\document\ContentStream;
use pdf\document\GraphicsStateParameterDictionary;
use pdf\object\PdfIndirectReference;
use pdf\object\PdfName;

/**
 * Operator, welcher ein externes Dictionary mit Graphics State einlädt
 * @package pdf\graphics\operator
 */
class ExternalGraphicsStateOperator extends AbstractOperator
{
    /**
     * Name des Dictionarys, angegeben im ResourceDictionary unter ExtGState
     * @var PdfName
     */
    protected $dictionaryName;
    /**
     * Dictionary mit dem GraphicsState, dereferenziert aus dem ResourceDictionary
     * @var GraphicsStateParameterDictionary
     */
    protected $externalGraphicsState;

    /**
     * ExternalGraphicsStateOperator constructor.
     * @param PdfName $dictionaryName Name des Dictionarys, angegeben im ResourceDictionary unter ExtGState
     * @param GraphicsStateParameterDictionary $externalGraphicsState Dictionary mit dem GraphicsState, dereferenziert aus dem ResourceDictionary
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfName $dictionaryName, GraphicsStateParameterDictionary $externalGraphicsState, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->dictionaryName = $dictionaryName;
        $this->externalGraphicsState = $externalGraphicsState;
    }

    /**
     * Erzeugt einen ExternalGraphicsStateOperator, wobei der External Graphics State aus dem ResourceDictionary eines ContentStreams ausgelesen wird.
     * @param PdfName $dictionaryName Name des Dictionarys, angegeben im ResourceDictionary unter ExtGState
     * @param ContentStream $contentStream ContentStream mit dem ResourceDictionary, aus welchem der ExtGState ausgelesen wird
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     * @return ExternalGraphicsStateOperator
     * @throws \Exception Wenn der External Graphics State nicht bekommen werden kann
     */
    public static function constructFromContentStream(PdfName $dictionaryName, ContentStream $contentStream, OperatorMetadata $operatorMetadata = null) : ExternalGraphicsStateOperator {
        $extGStateReference = $contentStream->getResourceDictionary()->getExtGStateDictionary()->getObject($dictionaryName->getValue());
        if ($extGStateReference === null)
            throw new \Exception("External Graphics State {$dictionaryName->toString()} is not Found");
        $externalGraphicsState = new GraphicsStateParameterDictionary($extGStateReference, $contentStream->getPdfFile());
        return new ExternalGraphicsStateOperator($dictionaryName, $externalGraphicsState, $operatorMetadata);
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "gs";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->dictionaryName->toString() . " gs\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfName
     */
    public function getDictionaryName(): PdfName
    {
        return $this->dictionaryName;
    }

    /**
     * @return GraphicsStateParameterDictionary
     */
    public function getExternalGraphicsState(): GraphicsStateParameterDictionary
    {
        return $this->externalGraphicsState;
    }
}