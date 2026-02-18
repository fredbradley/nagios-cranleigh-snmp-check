<?php declare(strict_types=1);

namespace FredBradley\CranleighSNMP\Tests;

use FredBradley\CranleighSNMP\CranleighSNMPCheck;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The constructor of CranleighSNMPCheck immediately calls shell_exec via
 * getLoad() and getRuntime(). Both invocations use the same command and receive
 * the same raw string; parsing extracts different sections (OUTPUT vs BATTERY).
 *
 * The namespace-scoped shell_exec stub (defined in tests/bootstrap.php) returns
 * whatever is stored in $GLOBALS['__snmp_fixture'], giving us full control
 * without hitting real hardware or using a mocking library.
 *
 * Fixture format (both sections must be present):
 *   BATTERY:(Runtime <n> <unit>, <mm>:<ss>) OUTPUT:(<V>, <Hz>, Load <n>%)
 */
final class CranleighSNMPCheckTest extends TestCase
{
    // Verified parse results for each fixture are documented inline.

    /** load 25%, runtime 2 Hours (120 min) → ok */
    private const FIXTURE_OK =
        'BATTERY:(Runtime 2 Hours, 30:00) OUTPUT:(231.1V, 50.0Hz, Load 25%)';

    /** load 75%, runtime 90 Minutes → warning (load > 70) */
    private const FIXTURE_WARNING_LOAD =
        'BATTERY:(Runtime 90 Minutes, 00:00) OUTPUT:(231.1V, 50.0Hz, Load 75%)';

    /** load 25%, runtime 25 Minutes → warning (runtime < 30) */
    private const FIXTURE_WARNING_RUNTIME =
        'BATTERY:(Runtime 25 Minutes, 00:00) OUTPUT:(231.1V, 50.0Hz, Load 25%)';

    /** load 85%, runtime 15 Minutes → critical (load > 80 AND runtime < 20) */
    private const FIXTURE_CRITICAL =
        'BATTERY:(Runtime 15 Minutes, 00:00) OUTPUT:(231.1V, 50.0Hz, Load 85%)';

    /** load 85%, runtime 90 Minutes → critical (load > 80 alone) */
    private const FIXTURE_CRITICAL_LOAD_ONLY =
        'BATTERY:(Runtime 90 Minutes, 00:00) OUTPUT:(231.1V, 50.0Hz, Load 85%)';

    /** load 25%, runtime 10 Minutes → critical (runtime < 20 alone) */
    private const FIXTURE_CRITICAL_RUNTIME_ONLY =
        'BATTERY:(Runtime 10 Minutes, 00:00) OUTPUT:(231.1V, 50.0Hz, Load 25%)';

    protected function tearDown(): void
    {
        unset($GLOBALS['__snmp_fixture']);
    }

    private function make(string $fixture, string $host = '192.168.1.1'): CranleighSNMPCheck
    {
        $GLOBALS['__snmp_fixture'] = $fixture;
        return new CranleighSNMPCheck($host);
    }

    // -------------------------------------------------------------------------
    // Load parsing
    // -------------------------------------------------------------------------

    public function testLoadPercentParsedFromOkFixture(): void
    {
        $this->assertSame(25, $this->make(self::FIXTURE_OK)->loadPercent);
    }

    public function testLoadPercentParsedFromWarningFixture(): void
    {
        $this->assertSame(75, $this->make(self::FIXTURE_WARNING_LOAD)->loadPercent);
    }

    public function testLoadPercentParsedFromCriticalFixture(): void
    {
        $this->assertSame(85, $this->make(self::FIXTURE_CRITICAL)->loadPercent);
    }

    // -------------------------------------------------------------------------
    // Runtime parsing
    // -------------------------------------------------------------------------

    public function testRuntimeInHoursParsed(): void
    {
        // "Runtime 2 Hours" → $runtime = 2
        $this->assertSame(2, $this->make(self::FIXTURE_OK)->runtime);
    }

    public function testRuntimeInMinutesParsed(): void
    {
        // "Runtime 25 Minutes" → $runtime = 25
        $this->assertSame(25, $this->make(self::FIXTURE_WARNING_RUNTIME)->runtime);
    }

    // -------------------------------------------------------------------------
    // displayBlock(): CSS class
    // -------------------------------------------------------------------------

    public function testDisplayBlockOkClass(): void
    {
        $html = $this->make(self::FIXTURE_OK)->displayBlock();
        $this->assertStringContainsString('class="ups ok"', $html);
    }

    public function testDisplayBlockWarningOnHighLoad(): void
    {
        // load 75% > 70 → warning
        $html = $this->make(self::FIXTURE_WARNING_LOAD)->displayBlock();
        $this->assertStringContainsString('class="ups warning"', $html);
    }

    public function testDisplayBlockWarningOnLowRuntime(): void
    {
        // 25 min < 30 threshold → warning
        $html = $this->make(self::FIXTURE_WARNING_RUNTIME)->displayBlock();
        $this->assertStringContainsString('class="ups warning"', $html);
    }

    public function testDisplayBlockCriticalOnHighLoadAndLowRuntime(): void
    {
        // load 85% > 80 AND 15 min < 20 → critical
        $html = $this->make(self::FIXTURE_CRITICAL)->displayBlock();
        $this->assertStringContainsString('class="ups notok"', $html);
    }

    public function testDisplayBlockCriticalOnHighLoadAlone(): void
    {
        // load 85% > 80, runtime 90 min is fine — load alone triggers critical
        $html = $this->make(self::FIXTURE_CRITICAL_LOAD_ONLY)->displayBlock();
        $this->assertStringContainsString('class="ups notok"', $html);
    }

    public function testDisplayBlockCriticalOnLowRuntimeAlone(): void
    {
        // load 25% is fine, 10 min < 20 alone triggers critical
        $html = $this->make(self::FIXTURE_CRITICAL_RUNTIME_ONLY)->displayBlock();
        $this->assertStringContainsString('class="ups notok"', $html);
    }

    // -------------------------------------------------------------------------
    // displayBlock(): HTML structure
    // -------------------------------------------------------------------------

    public function testDisplayBlockWrapsInUpsDiv(): void
    {
        $html = $this->make(self::FIXTURE_OK)->displayBlock();
        $this->assertMatchesRegularExpression('/^<div class="ups \w+">.+<\/div>$/s', $html);
    }

    public function testDisplayBlockContainsLoadLabel(): void
    {
        $html = $this->make(self::FIXTURE_OK)->displayBlock();
        $this->assertStringContainsString('Load:', $html);
    }

    public function testDisplayBlockContainsRuntimeLabel(): void
    {
        $html = $this->make(self::FIXTURE_OK)->displayBlock();
        $this->assertStringContainsString('Runtime:', $html);
    }

    public function testDisplayBlockSeparatesLoadAndRuntimeWithBreaks(): void
    {
        $html = $this->make(self::FIXTURE_OK)->displayBlock();
        $this->assertStringContainsString('<br /><br />', $html);
    }

    // -------------------------------------------------------------------------
    // Constructor: promoted readonly properties
    // -------------------------------------------------------------------------

    /** @return array<string, array{string}> */
    public static function hostProvider(): array
    {
        return [
            'ipv4'     => ['10.0.0.1'],
            'hostname' => ['ups.example.local'],
        ];
    }

    #[DataProvider('hostProvider')]
    public function testConstructionSucceedsForVariousHosts(string $host): void
    {
        $check = $this->make(self::FIXTURE_OK, $host);
        $this->assertInstanceOf(CranleighSNMPCheck::class, $check);
    }

    public function testDefaultCommunityNameAllowsConstruction(): void
    {
        // No community name passed — defaults to 'public'.
        $GLOBALS['__snmp_fixture'] = self::FIXTURE_OK;
        $check = new CranleighSNMPCheck('192.168.1.1');
        $this->assertSame(25, $check->loadPercent);
    }
}
