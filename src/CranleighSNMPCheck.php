<?php declare(strict_types=1);

namespace FredBradley\CranleighSNMP;

class CranleighSNMPCheck
{
    private readonly string $checkCommand;

    /** @var NagiosState[] */
    private array $states = [];

    public int $loadPercent;
    public int $runtime;
    private string $runtimeUnit;
    private string $showLoad;
    private string $showRuntime;

    public function __construct(
        private readonly string $host,
        private readonly string $communityName = 'public',
    ) {
        $this->checkCommand = '/usr/lib64/nagios/plugins/check_snmp_apcups';
        $this->showLoad     = $this->getLoad();
        $this->showRuntime  = $this->getRuntime();
    }

    private function setNagiosState(NagiosState $state): void
    {
        $this->states[] = $state;
    }

    private function calcClass(): string
    {
        usort($this->states, fn(NagiosState $a, NagiosState $b) => $b->value <=> $a->value);
        return $this->states[0]->cssClass();
    }

    public function displayBlock(): string
    {
        if ($this->loadPercent > 70 || $this->getRuntimeInMinutes() < 30) {
            $this->setNagiosState(NagiosState::Warning);
        } else {
            $this->setNagiosState(NagiosState::Ok);
        }

        if ($this->loadPercent > 80 || $this->getRuntimeInMinutes() < 20) {
            $this->setNagiosState(NagiosState::Critical);
        } else {
            $this->setNagiosState(NagiosState::Ok);
        }

        return sprintf(
            '<div class="ups %s">%s<br /><br />%s</div>',
            $this->calcClass(),
            $this->showLoad,
            $this->showRuntime,
        );
    }

    private function snmpCheck(string $type): string
    {
        $raw  = shell_exec($this->checkCommand . ' -C ' . $this->communityName . ' -H ' . $this->host) ?? '';
        $type = strtoupper($type);

        $known = ['BATTERY', 'INPUT', 'OUTPUT', 'SELF TEST', 'LAST EVENT'];

        if (!in_array($type, $known, true)) {
            return '?<br />';
        }

        $strlen       = strlen($type) + 2;
        $strpos       = (int) stripos($raw, $type . ':(');
        $substr       = substr($raw, $strpos + $strlen);
        $closeBracket = (int) stripos($substr, ')');

        return substr($substr, 0, $closeBracket) . '<br />';
    }

    private function getOutputSNMP(): array
    {
        $parts = explode(',', $this->snmpCheck('output'));

        return [
            'voltage'   => $this->tidyString($parts[0]),
            'frequency' => $this->tidyString($parts[1]),
            'load'      => $this->tidyString($parts[2]),
        ];
    }

    private function getBatterySNMP(): string
    {
        return $this->snmpCheck('battery');
    }

    private function tidyString(string $string): string
    {
        return trim(strip_tags($string));
    }

    private function getLoad(): string
    {
        $load  = $this->getOutputSNMP()['load'];
        $pos   = (int) stripos($load, '%');
        $label = ucwords(substr($load, 0, $pos + 1));
        $parts = explode(' ', $label);

        $this->loadPercent = (int) rtrim($parts[1], '%');

        return $parts[0] . ': ' . $parts[1];
    }

    private function getRuntime(): string
    {
        $battery     = $this->getBatterySNMP();
        $pos         = (int) stripos($battery, 'runtime');
        $after       = substr($battery, $pos + strlen('runtime'));
        $stringParts = explode(',', $after);
        $mainUnit    = trim($stringParts[0]);
        $secondUnit  = $this->simplifySecondUnit($stringParts[1]);
        $parts       = explode(' ', $mainUnit);

        $this->runtime     = (int) $parts[0];
        $this->runtimeUnit = $this->convertTime($parts[1]);

        $outputSecondUnit = $this->runtimeUnit === 'Mins' ? '' : $secondUnit . ' Mins';

        return sprintf('Runtime:<br />%s %s %s', $parts[0], $this->runtimeUnit, $outputSecondUnit);
    }

    private function simplifySecondUnit(string $secondUnit): int
    {
        $parts = explode(':', $this->tidyString($secondUnit));

        return (int) round((float) $parts[0]);
    }

    private function getRuntimeInMinutes(): int
    {
        return match ($this->runtimeUnit) {
            'Mins'          => $this->runtime,
            'Hour', 'Hours' => $this->runtime * 60,
            default         => 0,
        };
    }

    private function convertTime(string $input): string
    {
        return match (strtolower($input)) {
            'minutes' => 'Mins',
            'seconds' => 'Secs',
            default   => ucwords($input),
        };
    }
}
