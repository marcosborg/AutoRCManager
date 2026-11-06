<?php

namespace Tests\Unit;

use App\Support\PortugueseEuroAmount;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PortugueseEuroAmountTest extends TestCase
{
    public function test_amounts_are_written_in_portuguese_without_rounding(): void
    {
        foreach ([
            '0' => 'zero euros',
            '0.01' => 'zero euros e um cêntimo',
            '1.00' => 'um euro',
            '1.1' => 'um euro e dez cêntimos',
            '2.02' => 'dois euros e dois cêntimos',
            '16.17' => 'dezasseis euros e dezassete cêntimos',
            '100' => 'cem euros',
            '101' => 'cento e um euros',
            '1000' => 'mil euros',
            '1001' => 'mil e um euros',
            '1100' => 'mil e cem euros',
            '1101' => 'mil cento e um euros',
            '12500.50' => 'doze mil e quinhentos euros e cinquenta cêntimos',
            '1000000' => 'um milhão de euros',
            '2000000.01' => 'dois milhões de euros e um cêntimo',
            '1000001' => 'um milhão e um euros',
            '1001000' => 'um milhão e mil euros',
            '1100000' => 'um milhão e cem mil euros',
            '999999999.99' => 'novecentos e noventa e nove milhões novecentos e noventa e nove mil novecentos e noventa e nove euros e noventa e nove cêntimos',
            '00001.01' => 'um euro e um cêntimo',
            '.50' => 'zero euros e cinquenta cêntimos',
            '+1.20' => 'um euro e vinte cêntimos',
            '1.' => 'um euro',
            '+.01' => 'zero euros e um cêntimo',
        ] as $amount => $words) {
            $this->assertSame($words, PortugueseEuroAmount::words((string) $amount));
        }
        $this->assertSame('um euro e cinquenta cêntimos', PortugueseEuroAmount::words(1.5));
    }

    public function test_invalid_or_ambiguous_input_is_never_silently_converted(): void
    {
        foreach ([null, true, [], '', ' ', '.', '+', '+.', '1,50', '1.234,56', '1.234', '1e2', '-1', 'NaN', INF, '1000000000', '1.00x'] as $amount) {
            try {
                PortugueseEuroAmount::words($amount);
                $this->fail('Invalid amount was accepted: '.var_export($amount, true));
            } catch (InvalidArgumentException $expected) {
                $this->assertNotEmpty($expected->getMessage());
            }
        }
    }
}
