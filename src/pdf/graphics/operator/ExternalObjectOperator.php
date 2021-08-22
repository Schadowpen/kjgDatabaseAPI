<?php


namespace pdf\graphics\operator;


use pdf\document\ContentStream;
use pdf\document\XObject;
use pdf\graphics\Point;
use pdf\object\PdfName;

/**
 * Operator, welcher ein externes Objekt zeichnet
 * @package pdf\graphics\operator
 */
class ExternalObjectOperator extends AbstractImageOperator
{
    /**
     * Name des External Objects, angegeben im Resource Dictionary unter XObject
     * @var PdfName
     */
    protected $objectName;
    /**
     * Referenz auf das Externe Objekt, aus dem Resource Dictionary
     * @var XObject
     */
    protected $xObject;

    /**
     * ExternalObjectOperator constructor.
     * @param PdfName $objectName Name des External Objects, angegeben im Resource Dictionary unter XObject
     * @param XObject $xObject Referenz auf das Externe Objekt, aus dem Resource Dictionary
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfName $objectName, XObject $xObject, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->objectName = $objectName;
        $this->xObject = $xObject;
    }

    /**
     * Erzeugt einen ExternalObjectOperator, wobei das Externe Objekt aus dem übergebenen ContentStream ausgelesen wird
     * @param PdfName $objectName Name des External Objects, angegeben im Resource Dictionary unter XObject
     * @param ContentStream $contentStream ContentStream mit dem ResourceDictionary, aus welchem der ExtGState ausgelesen wird
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     * @return ExternalObjectOperator
     * @throws \Exception Wenn das XObjekt nicht gefunden werden kann
     */
    public static function constructFromContentStream(PdfName $objectName, ContentStream $contentStream, OperatorMetadata $operatorMetadata = null) : ExternalObjectOperator {
        $xObject = $contentStream->getResourceDictionary()->getXObject($objectName->getValue());
        if ($xObject === null)
            throw new \Exception("XObject {$objectName->toString()} is not Found");
        return new ExternalObjectOperator($objectName, $xObject, $operatorMetadata);
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Do";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "{$this->objectName->toString()} Do\n";
    }

    public function isRenderingOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfName
     */
    public function getObjectName(): PdfName
    {
        return $this->objectName;
    }

    /**
     * @return XObject
     */
    public function getXObject(): XObject
    {
        return $this->xObject;
    }

    /**
     * Liefert den Punkt unten links in Device Space
     * @return Point
     * @throws \Exception Wenn keine Metadaten zu diesem Operator angegeben wurden
     */
    function getLowerLeftCorner(): Point
    {
        return $this->getGraphicsState()->getCurrentTransformationMatrix()->transformPoint($this->xObject->getLowerLeftCorner());
    }

    /**
     * Liefert den Punkt unten rechts in Device Space
     * @return Point
     * @throws \Exception Wenn keine Metadaten zu diesem Operator angegeben wurden
     */
    function getLowerRightCorner(): Point
    {
        return $this->getGraphicsState()->getCurrentTransformationMatrix()->transformPoint($this->xObject->getLowerRightCorner());
    }

    /**
     * Liefert den Punkt oben links in Device Space
     * @return Point
     * @throws \Exception Wenn keine Metadaten zu diesem Operator angegeben wurden
     */
    function getUpperLeftCorner(): Point
    {
        return $this->getGraphicsState()->getCurrentTransformationMatrix()->transformPoint($this->xObject->getUpperLeftCorner());
    }

    /**
     * Liefert den Punkt oben rechts in Device Space
     * @return Point
     * @throws \Exception Wenn keine Metadaten zu diesem Operator angegeben wurden
     */
    function getUpperRightCorner(): Point
    {
        return $this->getGraphicsState()->getCurrentTransformationMatrix()->transformPoint($this->xObject->getUpperRightCorner());
    }

    /**
     * Liefert den Namen des Bildes. Namen müssen in einem Content Stream nicht einzigartig sein.
     * @return string
     */
    function getName(): string
    {
        return $this->objectName->getValue();
    }
}