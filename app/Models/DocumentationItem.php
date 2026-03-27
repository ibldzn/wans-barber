<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DocumentationItem extends Model
{
    public const FIELD_TYPE_TEXT = 'text';
    public const FIELD_TYPE_MULTILINE = 'multiline';
    public const FIELD_TYPE_DATE = 'date';
    public const FIELD_TYPE_PHONE = 'phone';
    public const FIELD_TYPE_NUMBER = 'number';
    public const FIELD_TYPE_URL = 'url';
    public const FIELD_TYPE_EMAIL = 'email';
    public const FIELD_TYPE_SECRET = 'secret';

    protected $fillable = [
        'label',
        'key',
        'field_type',
        'value',
        'description',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function getFieldTypeOptions(): array
    {
        return [
            static::FIELD_TYPE_TEXT => 'Text',
            static::FIELD_TYPE_MULTILINE => 'Multiline',
            static::FIELD_TYPE_DATE => 'Date',
            static::FIELD_TYPE_PHONE => 'Phone',
            static::FIELD_TYPE_NUMBER => 'Number',
            static::FIELD_TYPE_URL => 'URL',
            static::FIELD_TYPE_EMAIL => 'Email',
            static::FIELD_TYPE_SECRET => 'Secret',
        ];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function getPlainValue(): ?string
    {
        $value = $this->value;

        if (blank($value)) {
            return null;
        }

        if ($this->field_type !== static::FIELD_TYPE_SECRET) {
            return (string) $value;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            return (string) $value;
        }
    }

    public function getMaskedValue(): string
    {
        return blank($this->getPlainValue()) ? '-' : '••••••••';
    }

    public function getPreviewValue(): string
    {
        if ($this->field_type === static::FIELD_TYPE_SECRET) {
            return $this->getMaskedValue();
        }

        $plainValue = $this->getPlainValue();

        if (blank($plainValue)) {
            return '-';
        }

        return Str::limit(str_replace(["\r\n", "\n", "\r"], ' / ', $this->getFormattedValue() ?? $plainValue), 80);
    }

    public function getFormattedValue(): ?string
    {
        $plainValue = $this->getPlainValue();

        if (blank($plainValue)) {
            return null;
        }

        return match ($this->field_type) {
            static::FIELD_TYPE_DATE => $this->formatDateValue($plainValue),
            static::FIELD_TYPE_NUMBER => $this->formatNumberValue($plainValue),
            default => $plainValue,
        };
    }

    public function getLinkHref(): ?string
    {
        $plainValue = $this->getPlainValue();

        if (blank($plainValue)) {
            return null;
        }

        return match ($this->field_type) {
            static::FIELD_TYPE_PHONE => 'tel:' . preg_replace('/[^0-9+]/', '', $plainValue),
            static::FIELD_TYPE_EMAIL => 'mailto:' . $plainValue,
            static::FIELD_TYPE_URL => Str::startsWith($plainValue, ['http://', 'https://']) ? $plainValue : 'https://' . $plainValue,
            default => null,
        };
    }

    protected function formatDateValue(string $value): string
    {
        try {
            return Carbon::parse($value)->translatedFormat('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    protected function formatNumberValue(string $value): string
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $number = (float) $value;

        if ((int) $number === $number) {
            return number_format($number, 0, ',', '.');
        }

        return number_format($number, 2, ',', '.');
    }
}
