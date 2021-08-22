<?php

namespace pdf\document;

use pdf\object\PdfAbstractObject;
use pdf\object\PdfHexString;
use pdf\object\PdfString;

/**
 * Eine Zeitangabe in einer PDF-Datei
 * @package pdf\document
 */
class PdfDate
{
    /**
     * Gespeichertes Datum, Uhrzeit und Zeitzone
     * @var \DateTime
     */
    private $dateTime;

    /**
     * Erzeugt ein PdfDate aus einem DateTime-Objekt
     * @param \DateTime $dateTime Zu speichernde Datum, Uhrzeit und Zeitzone
     */
    public function __construct(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Gibt das gespeicherte Datum, Uhrzeit und Zeitzone zurück
     * @return \DateTime
     */
    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }

    /**
     * Setzt das gespeicherte Datum, Uhrzeit und Zeitzone
     * @param \DateTime $dateTime
     */
    public function setDateTime(\DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Erzeugt aus einem PdfString oder einem PdfHexString ein PdfDate.
     * Es wird nicht überprüft, ob der String tatsächlich ein Datum angibt.
     * @param PdfString|PdfHexString $pdfString zu parsender String aus der Pdf-Datei
     * @return \DateTime
     */
    public static function parsePdfString(PdfAbstractObject $pdfString): \DateTime
    {
        /** @var string $string */
        $string = $pdfString->getValue();

        $timeZoneString = substr($string, 16);
        if ($timeZoneString === false || $timeZoneString === "" || $timeZoneString === "Z") {
            $timeZone = new \DateTimeZone("UTC");
        } else {
            $timeZoneString[3] = ":";
            $timeZone = new \DateTimeZone($timeZoneString);
        }

        $dateTimeString = substr($string, 2, 14);
        $dateTimeStringLength = strlen($dateTimeString);
        if ($dateTimeStringLength === 4)
            $dateTimeString .= "0101000000";
        else if ($dateTimeStringLength === 6)
            $dateTimeString .= "01000000";
        else if ($dateTimeStringLength < 14)
            $dateTimeString = str_pad($dateTimeString, 14, "0");
        return \DateTime::createFromFormat("YmdGis", $dateTimeString, $timeZone);
    }

    /**
     * Liefert einen PdfString zurück, der die in das korrekte Format umgewandelte Zeit beinhaltet.
     * @param \DateTime $dateTime Umzuwandelnde Zeit
     * @return PdfString
     */
    public static function parseDateTime(\DateTime $dateTime): PdfString
    {
        $formatted = $dateTime->format("YmdHisP");
        $formatted[17] = "'";
        return new PdfString("D:" . $formatted);
    }
}