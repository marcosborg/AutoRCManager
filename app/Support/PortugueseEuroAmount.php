<?php

namespace App\Support;

use InvalidArgumentException;

final class PortugueseEuroAmount
{
    /** Format a decimal amount without rounding or guessing thousands separators. */
    public static function words(mixed $amount): string
    {
        if (!is_string($amount) && !is_int($amount) && !is_float($amount)) {
            throw new InvalidArgumentException('Expected a decimal euro amount.');
        }
        $value = (string) $amount;
        if (!preg_match('/^\+?([0-9]*)(?:\.([0-9]{0,2}))?$/D', $value, $matches)
            || ($matches[1] === '' && ($matches[2] ?? '') === '')) {
            throw new InvalidArgumentException('Invalid decimal euro amount.');
        }
        $whole = ltrim($matches[1], '0') ?: '0';
        if (strlen($whole) > 9) {
            throw new InvalidArgumentException('Euro amount exceeds the supported range.');
        }
        $euros = (int) $whole;
        $cents = (int) str_pad($matches[2] ?? '', 2, '0');
        $result = self::integer($euros).($euros >= 1000000 && $euros % 1000000 === 0 ? ' de' : '').($euros === 1 ? ' euro' : ' euros');

        return $result.($cents ? ' e '.self::integer($cents).($cents === 1 ? ' cêntimo' : ' cêntimos') : '');
    }

    private static function integer(int $number): string
    {
        $small = ['zero', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove',
            'dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezasseis', 'dezassete', 'dezoito', 'dezanove'];
        if ($number < 20) return $small[$number];
        if ($number < 100) {
            $tens = [2 => 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
            return $tens[intdiv($number, 10)].($number % 10 ? ' e '.self::integer($number % 10) : '');
        }
        if ($number === 100) return 'cem';
        if ($number < 1000) {
            $hundreds = [1 => 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
            return $hundreds[intdiv($number, 100)].($number % 100 ? ' e '.self::integer($number % 100) : '');
        }
        $scale = $number >= 1000000 ? 1000000 : 1000;
        $major = intdiv($number, $scale);
        $rest = $number % $scale;
        $prefix = $scale === 1000
            ? ($major === 1 ? 'mil' : self::integer($major).' mil')
            : self::integer($major).($major === 1 ? ' milhão' : ' milhões');
        $join = $rest < 100 || ($rest < 1000 && $rest % 100 === 0)
            || ($scale === 1000000 && $rest % 1000 === 0) ? ' e ' : ' ';
        return $prefix.($rest ? $join.self::integer($rest) : '');
    }
}
