<?php

namespace DailyMenuManager\Controller\Common;

class TranslationController
{
    public static function getFrontendTranslations()
    {
        return [
            'notes' => __('Notes for this item', DMM_TEXT_DOMAIN),
            'available' => __('available', DMM_TEXT_DOMAIN),
            // Rest der Übersetzungen
        ];
    }

    public static function getAdminTranslations()
    {

    }
}
