<?php

return [
    'status' => [
        'pending' => 'Ausstehend',
        'confirmed' => 'Bestätigt',
        'cancelled' => 'Storniert',
        'completed' => 'Abgeschlossen',
    ],

    'validation' => [
        'name_required' => 'Der Name ist erforderlich.',
        'email_required' => 'Die E-Mail-Adresse ist erforderlich.',
        'email_invalid' => 'Die E-Mail-Adresse muss eine gültige E-Mail-Adresse sein.',
        'phone_required' => 'Die Telefonnummer ist erforderlich.',
        'persons_required' => 'Die Anzahl der Personen ist erforderlich.',
        'persons_min' => 'Die Mindestanzahl der Personen beträgt 1.',
        'persons_max' => 'Die maximale Anzahl der Personen beträgt 20.',
        'date_required' => 'Das Datum ist erforderlich.',
        'date_future' => 'Bitte wählen Sie ein gültiges Datum.', // Hinweis: Vergangene Daten sind jetzt erlaubt
        'time_required' => 'Die Uhrzeit ist erforderlich.',
        'time_format' => 'Die Uhrzeit muss im Format HH:MM angegeben werden.',
        'time_operating_hours' => 'Reservierungen sind nur zwischen 11:00 und 22:00 Uhr möglich.',
        'status_required' => 'Der Status ist erforderlich.',
        'status_invalid' => 'Der ausgewählte Status ist ungültig.',
    ],

    'messages' => [
        'created' => 'Reservierung erfolgreich erstellt. Das Personal wird einen Tisch für Sie arrangieren.',
        'updated' => 'Reservierung erfolgreich aktualisiert.',
        'deleted' => 'Reservierung erfolgreich gelöscht.',
        'cancelled' => 'Reservierung erfolgreich storniert.',
        'status_updated' => 'Reservierungsstatus erfolgreich aktualisiert.',
        'not_found' => 'Reservierung nicht gefunden.',
        'cannot_update' => 'Diese Reservierung kann nicht mehr geändert werden.',
        'cannot_cancel' => 'Diese Reservierung kann nicht mehr storniert werden.',
        'unauthorized' => 'Sie sind nicht berechtigt, diese Aktion durchzuführen.',
    ],

    'availability' => [
        'available' => 'Verfügbar für die gewünschte Zeit und Personenzahl.',
        'not_available' => 'Nicht verfügbar. Verbleibende Kapazität: :remaining Personen.',
        'checking' => 'Verfügbarkeit wird überprüft...',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'E-Mail-Adresse',
        'phone' => 'Telefonnummer',
        'persons' => 'Anzahl Personen',
        'date' => 'Datum',
        'time' => 'Uhrzeit',
        'status' => 'Status',
        'admin_notes' => 'Admin-Notizen',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Zuletzt aktualisiert',
    ],

    'actions' => [
        'create' => 'Reservierung erstellen',
        'update' => 'Reservierung aktualisieren',
        'delete' => 'Reservierung löschen',
        'cancel' => 'Reservierung stornieren',
        'confirm' => 'Reservierung bestätigen',
        'complete' => 'Reservierung abschließen',
        'view' => 'Reservierung anzeigen',
        'edit' => 'Reservierung bearbeiten',
    ],
];