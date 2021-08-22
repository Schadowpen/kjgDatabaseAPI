<?php


namespace pdf\crossReference;


class CompressedObjectCrossReferenceTableEntry extends CrossReferenceTableEntry
{
    /**
     * Objektnummer des Objekt Streams, in welchem dieses Objekt gespeichert ist
     * @var int
     */
    protected $objectStreamNumber;

    /**
     * Index dieses Objektes in dem Objekt Stream
     * @var int
     */
    protected $indexInObjectStream;

    /**
     * Erzeugt einen neuen Eintrag.
     * @param $objNumber int Nummer des Objektes
     * @param $objectStreamNumber int Objektnummer des Objekt Streams, in welchem dieses Objekt gespeichert ist
     * @param $inUse bool Ob das Objekt genutzt (n/true) oder frei (f/false) ist
     * @param int $indexInObjectStream Index dieses Objektes in dem Objekt Stream
     */
    public function __construct(int $objNumber, int $objectStreamNumber, bool $inUse, int $indexInObjectStream)
    {
        parent::__construct($objNumber, 0, $inUse, -1);
        $this->objectStreamNumber = $objectStreamNumber;
        $this->indexInObjectStream = $indexInObjectStream;
    }

    /**
     * @return int
     */
    public function getObjectStreamNumber(): int
    {
        return $this->objectStreamNumber;
    }

    /**
     * @return int
     */
    public function getIndexInObjectStream(): int
    {
        return $this->indexInObjectStream;
    }
}