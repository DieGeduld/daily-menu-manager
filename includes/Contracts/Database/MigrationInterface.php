<?php

namespace DailyMenuManager\Contracts\Database;

/**
 * Interface MigrationInterface
 *
 * Definiert den Vertrag für Datenbankmigrationen im Daily Menu Manager Plugin.
 * Alle Migrationen müssen dieses Interface implementieren, um Konsistenz
 * und einheitliches Verhalten zu gewährleisten.
 *
 * @package DailyMenuManager\Contracts\Database
 */
interface MigrationInterface
{
    /**
     * Gibt an, ob die Migration automatisch ausgeführt werden kann.
     *
     * Bestimmt, ob die Migration im Rahmen automatischer Updates ohne
     * Benutzerinteraktion ausgeführt werden soll.
     *
     * @return bool True wenn die Migration automatisch ausgeführt werden kann, sonst false
     */
    public function canAutorun(): bool;

    /**
     * Führt die Migration aus.
     *
     * Diese Methode enthält die Logik für die Vorwärts-Migration,
     * z.B. Erstellen von Tabellen, Hinzufügen von Spalten etc.
     *
     * @throws \RuntimeException Wenn die Migration fehlschlägt
     * @return void
     */
    public function up(): void;

    /**
     * Macht die Migration rückgängig.
     *
     * Diese Methode enthält die Logik für die Rückwärts-Migration,
     * z.B. Löschen von Tabellen, Entfernen von Spalten etc.
     *
     * @throws \RuntimeException Wenn das Rückgängigmachen fehlschlägt
     * @return void
     */
    public function down(): void;

    /**
     * Gibt die Version der Migration zurück.
     *
     * Die Version sollte dem semantischen Versionierungsformat folgen (x.y.z).
     * Beispiel: "1.0.0" für die erste Version.
     *
     * @return string Die Versionsnummer
     */
    public function getVersion(): string;

    /**
     * Gibt die Beschreibung der Migration zurück.
     *
     * Die Beschreibung sollte kurz und präzise erklären,
     * was die Migration macht.
     *
     * @return string Die Beschreibung der Migration
     */
    public function getDescription(): string;

    /**
     * Prüft, ob die Migration rückgängig gemacht werden kann.
     *
     * Einige Migrationen, wie das Löschen von Daten, können nicht
     * rückgängig gemacht werden.
     *
     * @return bool True wenn die Migration reversibel ist, sonst false
     */
    public function isReversible(): bool;

    /**
     * Gibt die Abhängigkeiten der Migration zurück.
     *
     * Eine Liste von Migrations-Versionen, die vor dieser Migration
     * ausgeführt werden müssen.
     *
     * @return array<string> Array mit Versionsnummern der Abhängigkeiten
     */
    public function getDependencies(): array;

    /**
     * Gibt die von der Migration betroffenen Tabellen zurück.
     *
     * Diese Information wird für Logging und Abhängigkeitsprüfungen verwendet.
     *
     * @return array<string> Array mit Tabellennamen
     */
    public function getAffectedTables(): array;

    /**
     * Setzt die Batch-Größe für die Migration.
     *
     * Bei großen Datenmengen sollten die Operationen in kleineren
     * Batches ausgeführt werden.
     *
     * @param int $size Die Anzahl der Datensätze pro Batch
     * @return void
     */
    public function setBatchSize(int $size): void;

    /**
     * Prüft, ob alle Voraussetzungen für die Migration erfüllt sind.
     *
     * Z.B. ob erforderliche Tabellen existieren oder Abhängigkeiten
     * installiert sind.
     *
     * @throws \RuntimeException Wenn Voraussetzungen nicht erfüllt sind
     * @return bool True wenn alle Voraussetzungen erfüllt sind
     */
    public function validatePrerequisites(): bool;

    /**
     * Gibt eine eindeutige ID für die Migration zurück.
     *
     * Die ID wird für das Tracking des Migrationsstatus verwendet.
     * Standardmäßig kann die Version als ID verwendet werden.
     *
     * @return string Die eindeutige ID der Migration
     */
    public function getId(): string;

    /**
     * Gibt den Zeitstempel der Migration zurück.
     *
     * Wird verwendet, um die Reihenfolge der Migrationen zu bestimmen,
     * wenn mehrere Migrationen die gleiche Version haben.
     *
     * @return int UNIX Timestamp der Erstellung
     */
    public function getTimestamp(): int;
}
