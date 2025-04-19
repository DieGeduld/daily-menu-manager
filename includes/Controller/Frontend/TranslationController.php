<?php

namespace DailyMenuManager\Controller\Common;

class TranslationController
{
    public static function getFrontendTranslations()
    {
        return [
            'notes' => __('Notes for this item', 'daily-menu-manager'),
            'available' => __('available', 'daily-menu-manager'),
            // Rest der Übersetzungen
        ];
    }

    public static function getAdminTranslations()
    {

    }
}
