<?php
namespace DailyMenuManager\Helper;

use DateTime;

class DateUtils {
    /**
     * Konvertiert eine Zeit im 24-Stunden-Format zu einem 12-Stunden-Format
     *
     * @param string $time Zeit im Format "H:i" (z.B. "14:30")
     * @return string Zeit im Format "g:i A" (z.B. "2:30 PM")
     */
    public static function convertTimeToFormat($time, $format = 'H:i') {
        // Erstellt ein DateTime-Objekt mit dem übergebenen Zeitstring
        $dateTime = DateTime::createFromFormat('H:i', $time);
        
        // Prüft, ob das Parsing erfolgreich war
        if ($dateTime === false) {
            return 'Ungültiges Zeitformat. Bitte verwende das Format "H:i" (z.B. "14:30").';
        }
        
        // Formatiert die Zeit im 12-Stunden-Format
        return $dateTime->format($format);
    }
}
