<?php

return [
    // Gateway messages
    'gateway_not_available' => 'Zahlungsgateway ist nicht verfügbar',
    'gateways_retrieved' => 'Zahlungsgateways erfolgreich abgerufen',
    'gateways_error' => 'Fehler beim Abrufen der Zahlungsgateways',
    
    // Validation messages
    'validation_failed' => 'Validierung fehlgeschlagen',
    'invalid_amount' => 'Ungültiger Betrag',
    'currency_not_supported' => 'Währung wird nicht unterstützt',
    'order_description' => 'Bestellung :order_code',
    
    // Validation specific
    'validation' => [
        'amount_required' => 'Betrag ist erforderlich',
        'amount_numeric' => 'Betrag muss eine Zahl sein',
        'amount_min' => 'Betrag muss größer als 0 sein',
        'amount_invalid' => 'Ungültiger Betrag',
        'order_id_required' => 'Bestell-ID ist erforderlich',
        'order_id_exists' => 'Bestellung existiert nicht',
        'currency_format' => 'Währung muss 3 Zeichen haben',
        'currency_not_supported' => 'Diese Währung wird nicht unterstützt',
        'payment_method_invalid' => 'Ungültige Zahlungsmethode',
    ],
    
    // Stripe specific
    'stripe' => [
        'intent_created' => 'Stripe PaymentIntent erfolgreich erstellt',
        'intent_failed' => 'Fehler beim Erstellen des Stripe PaymentIntent',
    ],
    
    // PayPal specific
    'paypal' => [
        'order_created' => 'PayPal-Bestellung erfolgreich erstellt',
        'order_failed' => 'Fehler beim Erstellen der PayPal-Bestellung',
    ],
    
    // Payment status
    'status' => [
        'pending' => 'Ausstehend',
        'completed' => 'Abgeschlossen',
        'failed' => 'Fehlgeschlagen',
        'refunded' => 'Erstattet',
    ],
    
    // Payment methods
    'method' => [
        'cash' => 'Bargeld',
        'stripe' => 'Kreditkarte (Stripe)',
        'paypal' => 'PayPal',
    ],
    
    // General messages
    'payment_processing' => 'Zahlung wird verarbeitet...',
    'payment_successful' => 'Zahlung erfolgreich',
    'payment_failed' => 'Zahlung fehlgeschlagen',
    'payment_cancelled' => 'Zahlung abgebrochen',
    'payment_refunded' => 'Zahlung erstattet',
    
    // Error messages
    'webhook_processing_failed' => 'Webhook-Verarbeitung fehlgeschlagen',
    'transaction_not_found' => 'Transaktion nicht gefunden',
    'order_not_found' => 'Bestellung nicht gefunden',
];