<?php

namespace Application\Util;

use Laminas\Filter\FilterChain;
use Laminas\Filter\StripNewlines;
use Laminas\Filter\StripTags;
use Laminas\Filter\StringTrim;

final class InputSanitizer
{
    private static ?FilterChain $filter = null;

    private static function getFilter(): FilterChain
    {
        if (self::$filter === null) {
            $filter = new FilterChain();
            $filter->attach(new StringTrim())
                ->attach(new StripNewlines())
                ->attach(new StripTags());
            self::$filter = $filter;
        }

        return self::$filter;
    }

    public static function cleanString($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return '';
        }

        return (string) self::getFilter()->filter((string) $value);
    }

    public static function cleanArray(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::cleanArray($value);
            } else {
                $clean[$key] = self::cleanString($value);
            }
        }

        return $clean;
    }

    public static function cleanInt($value): int
    {
        return (int) self::cleanString($value);
    }

    public static function cleanBool($value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
}
