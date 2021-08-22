<?php


namespace database;

/**
 * Speichert, dass eine DatabaseConnection gelockt werden soll.
 * Es wird auch gespeichert, ob es ein read- oder writelock sein soll.
 * @package database
 */
class DatabaseLockHandle
{
    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    /**
     * @var int
     */
    private $lockStatus;

    /**
     * DatabaseLockHandle constructor.
     * @param DatabaseConnection $databaseConnection welche gelockt werden soll
     * @param int $lockStatus LOCK_SH für Readlock, LOCK_EX für Writelock.
     */
    public function __construct(DatabaseConnection $databaseConnection, int $lockStatus)
    {
        if ($lockStatus != LOCK_SH && $lockStatus != LOCK_EX)
            $lockStatus = LOCK_EX;
        $this->databaseConnection = $databaseConnection;
        $this->lockStatus = $lockStatus;
    }

    /**
     * @return DatabaseConnection
     */
    public function getDatabaseConnection(): DatabaseConnection
    {
        return $this->databaseConnection;
    }

    /**
     * @return int
     * @see LOCK_EX
     * @see LOCK_SH
     */
    public function getLockStatus(): int
    {
        return $this->lockStatus;
    }

    /**
     * Führt den read- oder writelock auf der DatabaseConnection aus
     * @param bool $echoErrors Ob etwaige Fehler direkt mit echo in die Ausgabe geschrieben werden sollen, default true
     * @return bool Ob der lock gesetzt werden konnte
     */
    public function lockDatabase(bool $echoErrors = true): bool
    {
        if ($this->lockStatus == LOCK_SH)
            return $this->databaseConnection->readlockDatabase($echoErrors);
        elseif ($this->lockStatus == LOCK_EX)
            return $this->databaseConnection->writelockDatabase($echoErrors);
        else
            return false;
    }
}