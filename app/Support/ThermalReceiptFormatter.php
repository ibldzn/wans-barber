<?php

namespace App\Support;

class ThermalReceiptFormatter
{
    public function __construct(
        private readonly int $width = 32,
    ) {}

    public function width(): int
    {
        return $this->width;
    }

    public function hr(string $char = '-'): string
    {
        return str_repeat($char, $this->width);
    }

    public function center(string $text): string
    {
        $text = $this->sanitize($text);
        $textWidth = $this->strWidth($text);

        if ($textWidth >= $this->width) {
            return $this->trimToWidth($text, $this->width);
        }

        $padding = (int) floor(($this->width - $textWidth) / 2);

        return str_repeat(' ', $padding) . $text;
    }

    /**
     * @return array<int, string>
     */
    public function row(string $left, string $right = ''): array
    {
        $left = $this->sanitize($left);
        $right = $this->sanitize($right);

        if ($right === '') {
            return $this->wrap($left);
        }

        $rightWidth = $this->strWidth($right);

        // If right side is too long, place it on a dedicated line.
        if ($rightWidth >= ($this->width - 1)) {
            $lines = $this->wrap($left);
            $lines[] = $right;

            return $lines;
        }

        $leftWidth = $this->width - $rightWidth - 1;
        $leftLines = $this->wrap($left, $leftWidth);

        if ($leftLines === []) {
            $leftLines = [''];
        }

        $firstLeft = array_shift($leftLines);
        $firstLineGap = max(1, $this->width - $this->strWidth($firstLeft) - $rightWidth);

        $lines = [$firstLeft . str_repeat(' ', $firstLineGap) . $right];

        foreach ($leftLines as $line) {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Header row with fixed label + colon column and right-aligned value.
     *
     * @return array<int, string>
     */
    public function labelValueRight(string $label, string $value, int $labelWidth = 10): array
    {
        $label = $this->sanitize($label);
        $value = $this->sanitize($value);

        $labelCell = str_pad($this->trimToWidth($label, $labelWidth), $labelWidth, ' ');
        $prefix = $labelCell . ' : ';
        $prefixWidth = $this->strWidth($prefix);
        $valueWidth = max(1, $this->width - $prefixWidth);

        $valueLines = $this->wrap($value, $valueWidth);

        if ($valueLines === []) {
            $valueLines = ['-'];
        }

        $lines = [];

        foreach ($valueLines as $index => $lineValue) {
            $lineValueWidth = $this->strWidth($lineValue);
            $padding = max(0, $valueWidth - $lineValueWidth);
            $linePrefix = $index === 0 ? $prefix : str_repeat(' ', $prefixWidth);

            $lines[] = $linePrefix . str_repeat(' ', $padding) . $lineValue;
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    public function wrap(string $text, ?int $width = null): array
    {
        $text = trim($this->sanitize($text));

        if ($text === '') {
            return [];
        }

        $width ??= $this->width;

        if ($this->strWidth($text) <= $width) {
            return [$text];
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;

            if ($this->strWidth($candidate) <= $width) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                $current = '';
            }

            if ($this->strWidth($word) <= $width) {
                $current = $word;
                continue;
            }

            // Split very long token (e.g. long invoice/reference string)
            while ($this->strWidth($word) > $width) {
                $chunk = $this->trimToWidth($word, $width);
                $lines[] = $chunk;
                $word = function_exists('mb_substr')
                    ? mb_substr($word, mb_strlen($chunk, 'UTF-8'), null, 'UTF-8')
                    : substr($word, strlen($chunk));
            }

            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function sanitize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function strWidth(string $text): int
    {
        return function_exists('mb_strwidth') ? mb_strwidth($text, 'UTF-8') : strlen($text);
    }

    private function trimToWidth(string $text, int $width): string
    {
        if (function_exists('mb_strimwidth')) {
            return rtrim(mb_strimwidth($text, 0, $width, '', 'UTF-8'));
        }

        return rtrim(substr($text, 0, $width));
    }
}
