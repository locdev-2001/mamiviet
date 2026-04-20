<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HandlesTranslatableFormData
{
    /**
     * Build form data array from a translatable model: [locale => [field => value]].
     */
    protected static function unpackTranslations(Model $record, array $fields, array $locales): array
    {
        $out = [];
        foreach ($locales as $code) {
            foreach ($fields as $field) {
                $out[$code][$field] = $record->getTranslation($field, $code, false);
            }
        }

        return $out;
    }

    /**
     * Apply translatable fields from form data onto the record. Empty string clears the locale.
     */
    protected static function applyTranslations(Model $record, array $data, array $fields, array $locales): void
    {
        foreach ($fields as $field) {
            foreach ($locales as $code) {
                $value = $data[$code][$field] ?? null;
                if ($value === null || $value === '' || $value === []) {
                    $record->forgetTranslation($field, $code);
                } else {
                    $record->setTranslation($field, $code, $value);
                }
            }
        }
    }

    /**
     * Strip locale-keyed entries from form data, leaving only top-level fields.
     */
    protected static function stripLocaleKeys(array $data, array $locales): array
    {
        foreach ($locales as $code) {
            unset($data[$code]);
        }

        return $data;
    }
}
