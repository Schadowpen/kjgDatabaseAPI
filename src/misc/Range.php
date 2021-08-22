<?php


namespace misc;

/**
 * Ein Bereich von Start (inklusive) bis Ende (exklusive),
 * vorzugsweise für das Arbeiten mit Arraypositionen
 * @package misc
 */
class Range
{
    /**
     * Startindex (inklusive) des Bereiches
     * @var int
     */
    protected $startIndex;
    /**
     * Endindex (exklusive) des Bereiches
     * @var int
     */
    protected $endIndex;

    /**
     * Erzeugt einen neuen Bereich
     * @param int $startIndex Startindex (inklusive) des Bereiches
     * @param int $endIndex Endindex (exklusive) des Bereiches
     */
    public function __construct(int $startIndex, int $endIndex)
    {
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
    }

    /**
     * @return int
     */
    public function getStartIndex(): int
    {
        return $this->startIndex;
    }

    /**
     * @param int $startIndex
     */
    public function setStartIndex(int $startIndex): void
    {
        $this->startIndex = $startIndex;
    }

    /**
     * Verringert den startIndex um 1
     */
    public function decreaseStartIndex(): void
    {
        --$this->startIndex;
    }

    /**
     * @return int
     */
    public function getEndIndex(): int
    {
        return $this->endIndex;
    }

    /**
     * @param int $endIndex
     */
    public function setEndIndex(int $endIndex): void
    {
        $this->endIndex = $endIndex;
    }

    /**
     * Erhöht den endIndex um 1
     */
    public function increaseEndIndex(): void
    {
        ++$this->endIndex;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->endIndex - $this->startIndex;
    }

    /**
     * Der StartIndex bleibt dabei gleich, nur der EndIndex wird neu berechnet.
     * @param int $length Neue Länge des Bereiches
     */
    public function setLength(int $length)
    {
        $this->endIndex = $this->startIndex + $length;
    }
}