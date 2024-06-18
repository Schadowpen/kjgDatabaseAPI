<?php
/**
 * Konfigurationsdatei als zentraler Sammelpunkt für alle "globalen" Konfigurationen
 */


/**
 * Wurzelverzeichnis der Datenbank, von welcher aus man zum aktuellen Datensatz mit 'current/' oder zum Archiv mit 'archive/' gelangt
 */
$databaseRootFolder = $_SERVER["DOCUMENT_ROOT"] . "/../kjg_Ticketing_database/";


/**
 * Sicherheitsschlüssel zum validieren von http-Anfragen.
 * Um einen aktuell gültigen Schlüssel zu erhalten, muss der Sicherheitsschlüssel mit dem aktuellen Datum und Uhrzeit im Format 'YYYY-MM-DDThh:mm' erweitert werden
 * und dann mit SHA256 gehasht werden.
 */
$securityKey = "lrzjkwxcxijgwzjbragkenofadshuizhl";

/**
 * Verzeichnis, in welchem die generierten Karten abgelegt werden.
 */
$ticketsFolder = $_SERVER["DOCUMENT_ROOT"] . "/karten/";