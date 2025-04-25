<?php

namespace DailyMenuManager\Service;

/**
 * Class LoggingService
 *
 * Ein Service für das Protokollieren von Nachrichten innerhalb des DailyMenuManager-Plugins.
 * Ermöglicht das Protokollieren auf verschiedenen Ebenen und an verschiedene Ziele.
 */
class LoggingService
{
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    /**
     * @var string Der Standardpfad für die Protokolldatei
     */
    protected string $logPath;

    /**
     * @var bool Gibt an, ob die Debug-Protokollierung aktiviert ist
     */
    protected bool $debugEnabled;

    /**
     * LoggingService Konstruktor.
     *
     * @param string|null $logPath Pfad zur Protokolldatei, oder null für den Standardpfad
     */
    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? DMM_PLUGIN_DIR . 'logs/plugin.log';
        $this->debugEnabled = defined('WP_DEBUG') && WP_DEBUG;

        $this->ensureLogDirectoryExists();
    }

    /**
     * Protokolliert eine Fehlermeldung.
     *
     * @param string $message Die Fehlermeldung
     * @param array $context Kontextdaten für die Protokollierung
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Protokolliert eine Warnmeldung.
     *
     * @param string $message Die Warnmeldung
     * @param array $context Kontextdaten für die Protokollierung
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Protokolliert eine Informationsmeldung.
     *
     * @param string $message Die Informationsmeldung
     * @param array $context Kontextdaten für die Protokollierung
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Protokolliert eine Debug-Meldung. Funktioniert nur, wenn WP_DEBUG aktiviert ist.
     *
     * @param string $message Die Debug-Meldung
     * @param array $context Kontextdaten für die Protokollierung
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        if ($this->debugEnabled) {
            $this->log(self::DEBUG, $message, $context);
        }
    }

    /**
     * Protokolliert eine Meldung mit dem angegebenen Schweregrad.
     *
     * @param string $level Der Schweregrad (error, warning, info, debug)
     * @param string $message Die zu protokollierende Meldung
     * @param array $context Zusätzliche Kontextdaten
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date("Y-m-d H:i:s");
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $caller = $this->getCallerInfo();

        $logMessage = sprintf(
            "[%s] [%s] [%s] %s%s" . PHP_EOL,
            $timestamp,
            strtoupper($level),
            $caller,
            $message,
            $contextString
        );

        $this->writeToLog($logMessage);

        // Bei Fehlern auch in die WordPress-Fehlerprotokollierung schreiben
        if ($level === self::ERROR && function_exists('error_log')) {
            error_log("DailyMenuManager Error: $message");
        }
    }

    /**
     * Stellt sicher, dass das Log-Verzeichnis existiert und beschreibbar ist.
     *
     * @return void
     */
    protected function ensureLogDirectoryExists(): void
    {
        $directory = dirname($this->logPath);

        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }

        // Prüfen, ob das Verzeichnis beschreibbar ist
        if (!is_writable($directory)) {
            // Versuchen, die Berechtigungen zu ändern
            chmod($directory, 0755);

            if (!is_writable($directory)) {
                error_log("DailyMenuManager: Das Log-Verzeichnis '$directory' ist nicht beschreibbar.");
            }
        }
    }

    /**
     * Schreibt eine Nachricht in die Protokolldatei.
     *
     * @param string $message Die zu schreibende Nachricht
     * @return void
     */
    protected function writeToLog(string $message): void
    {
        if (!file_exists($this->logPath)) {
            touch($this->logPath);
            chmod($this->logPath, 0644);
        }

        file_put_contents($this->logPath, $message, FILE_APPEND);
    }

    /**
     * Löscht die Protokolldatei.
     *
     * @return bool True bei Erfolg, False bei Misserfolg
     */
    public function clearLog(): bool
    {
        if (file_exists($this->logPath)) {
            return unlink($this->logPath);
        }

        return true;
    }

    /**
     * Bereinigt alte Protokolle basierend auf einem Alter in Tagen.
     *
     * @param int $daysToKeep Anzahl der Tage, die Protokolle behalten werden sollen
     * @return void
     */
    public function purgeOldLogs(int $daysToKeep = 30): void
    {
        $directory = dirname($this->logPath);
        $files = glob($directory . '/*.log');

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = filemtime($file);
                $purgeTime = time() - ($daysToKeep * 86400); // 86400 Sekunden pro Tag

                if ($fileTime < $purgeTime) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Holt Informationen über den aufrufenden Code zur besseren Nachverfolgung.
     *
     * @return string
     */
    protected function getCallerInfo(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = isset($trace[3]) ? $trace[3] : (isset($trace[2]) ? $trace[2] : $trace[1]);

        $class = isset($caller['class']) ? $caller['class'] : '';
        $function = isset($caller['function']) ? $caller['function'] : '';

        if ($class) {
            return $class . ($function ? "::$function" : '');
        }

        if ($function) {
            return $function;
        }

        return 'unknown';
    }
}
