<?php declare(strict_types=1);

namespace FredBradley\CranleighSNMP\Tests;

use FredBradley\CranleighSNMP\NagiosState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NagiosStateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Integer backing values
    // -------------------------------------------------------------------------

    public function testOkValueIsZero(): void
    {
        $this->assertSame(0, NagiosState::Ok->value);
    }

    public function testWarningValueIsOne(): void
    {
        $this->assertSame(1, NagiosState::Warning->value);
    }

    public function testCriticalValueIsTwo(): void
    {
        $this->assertSame(2, NagiosState::Critical->value);
    }

    public function testUnknownValueIsThree(): void
    {
        $this->assertSame(3, NagiosState::Unknown->value);
    }

    // -------------------------------------------------------------------------
    // cssClass() return values
    // -------------------------------------------------------------------------

    /** @return array<string, array{NagiosState, string}> */
    public static function cssClassProvider(): array
    {
        return [
            'ok'       => [NagiosState::Ok,       'ok'],
            'warning'  => [NagiosState::Warning,  'warning'],
            'critical' => [NagiosState::Critical, 'notok'],
            'unknown'  => [NagiosState::Unknown,  'unknown'],
        ];
    }

    #[DataProvider('cssClassProvider')]
    public function testCssClass(NagiosState $state, string $expected): void
    {
        $this->assertSame($expected, $state->cssClass());
    }

    // -------------------------------------------------------------------------
    // Backed-enum factory methods
    // -------------------------------------------------------------------------

    public function testFromIntReturnsCorrectCase(): void
    {
        $this->assertSame(NagiosState::Critical, NagiosState::from(2));
    }

    public function testTryFromValidValueReturnsCase(): void
    {
        $this->assertSame(NagiosState::Warning, NagiosState::tryFrom(1));
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(NagiosState::tryFrom(99));
    }

    // -------------------------------------------------------------------------
    // Completeness
    // -------------------------------------------------------------------------

    public function testEnumHasFourCases(): void
    {
        $this->assertCount(4, NagiosState::cases());
    }

    public function testCasesHaveUniqueValues(): void
    {
        $values = array_map(fn(NagiosState $s) => $s->value, NagiosState::cases());
        $this->assertSame($values, array_unique($values));
    }

    public function testCssClassesHaveUniqueStrings(): void
    {
        $classes = array_map(fn(NagiosState $s) => $s->cssClass(), NagiosState::cases());
        $this->assertSame($classes, array_unique($classes));
    }
}
