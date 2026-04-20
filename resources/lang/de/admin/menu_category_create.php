<?php

return [
    // Page Title & Headers
    'page_title' => 'Kategorie hinzufügen',
    'page_header' => 'Neue Kategorie hinzufügen',
    'breadcrumb_home' => 'Dashboard',
    'breadcrumb_categories' => 'Kategorien',
    'breadcrumb_create' => 'Kategorie hinzufügen',
    
    // Form Labels
    'form_name_label' => 'Kategoriename',
    'form_name_placeholder' => 'Kategoriename eingeben',
    'form_description_label' => 'Beschreibung',
    'form_description_placeholder' => 'Kategoriebeschreibung eingeben (optional)',
    'form_parent_label' => 'Übergeordnete Kategorie',
    'form_parent_none' => 'Keine (Hauptkategorie)',
    'form_image_label' => 'Kategoriebild',
    'form_position_label' => 'Position',
    'form_position_help' => 'Anzeigereihenfolge Position (0 = erste)',
    'form_status_label' => 'Status',
    'form_status_active' => 'Aktiv',
    'form_status_inactive' => 'Inaktiv',
    
    // Buttons
    'btn_back' => 'Zurück',
    'btn_cancel' => 'Abbrechen',
    'btn_save' => 'Kategorie speichern',
    'btn_saving' => 'Speichern...',
    'btn_remove_image' => 'Bild entfernen',
    
    // Messages
    'success_created' => 'Kategorie erfolgreich erstellt!',
    'error_validation' => 'Bitte überprüfen Sie das Formular auf Fehler.',
    'error_server' => 'Beim Speichern ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.',
    'error_unauthorized' => 'Sie sind nicht berechtigt, diese Aktion auszuführen.',
    'error_network' => 'Netzwerkfehler. Bitte überprüfen Sie Ihre Verbindung.',
    
    // Validation Messages
    'validation_name_required' => 'Kategoriename ist erforderlich.',
    'validation_name_max' => 'Kategoriename darf nicht länger als 255 Zeichen sein.',
    'validation_description_max' => 'Beschreibung darf nicht länger als 1000 Zeichen sein.',
    'validation_position_numeric' => 'Position muss eine Zahl sein.',
    'validation_position_min' => 'Position muss 0 oder größer sein.',
    
    // Required Field Indicator
    'required_field' => 'Pflichtfeld',
    'optional_field' => 'Optional',
    
    // Loading States
    'loading_save' => 'Kategorie speichern...',
    'loading_form' => 'Formular laden...',
    
    // Form Sections  
    'section_basic_info' => 'Grundinformationen',
    'section_settings' => 'Einstellungen',
    
    // Help Text
    'help_name' => 'Geben Sie einen eindeutigen Namen für diese Kategorie ein',
    'help_description' => 'Geben Sie eine kurze Beschreibung dessen an, was diese Kategorie enthält',
    'help_parent' => 'Wählen Sie eine übergeordnete Kategorie aus, um eine Unterkategorie zu erstellen',
    'help_image' => 'Laden Sie ein Bild für diese Kategorie hoch (JPG, PNG, GIF, SVG - max. 2MB)',
    'help_position' => 'Niedrigere Zahlen werden zuerst in der Liste angezeigt',
    'help_status' => 'Nur aktive Kategorien sind für Kunden sichtbar',
];