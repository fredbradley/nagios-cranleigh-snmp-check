# Cranleigh SNMP Check

A PHP wrapper class for checking APC UPS devices via SNMP, designed for use with Nagios / nagDash dashboards.

[![Latest Stable Version](https://poser.pugx.org/fredbradley/cranleigh-snmp-check/v/stable)](https://packagist.org/packages/fredbradley/cranleigh-snmp-check)
[![License](https://poser.pugx.org/fredbradley/cranleigh-snmp-check/license)](https://packagist.org/packages/fredbradley/cranleigh-snmp-check)

## Requirements

- PHP >= 8.4
- The `check_snmp_apcups` Nagios plugin installed on your server (default path: `/usr/lib64/nagios/plugins/check_snmp_apcups`)
- SNMP access to your UPS device

## Installation

```bash
composer require fredbradley/cranleigh-snmp-check
```

## Usage

```php
<?php declare(strict_types=1);

require 'vendor/autoload.php';

use FredBradley\CranleighSNMP\CranleighSNMPCheck;

// Pass the IP address of your UPS and (optionally) the SNMP community name
$ups = new CranleighSNMPCheck('192.168.1.100', 'public');

// Output an HTML block suitable for use in a nagDash dashboard
echo $ups->displayBlock();
```

The `displayBlock()` method returns an HTML `<div>` with a CSS class reflecting the current Nagios state:

| State    | CSS class | Trigger                                      |
|----------|-----------|----------------------------------------------|
| OK       | `ok`      | Load ≤ 70% and runtime ≥ 30 min              |
| Warning  | `warning` | Load > 70% **or** runtime < 30 min           |
| Critical | `notok`   | Load > 80% **or** runtime < 20 min           |
| Unknown  | `unknown` | State cannot be determined                   |

## Public Properties

After instantiation, the following properties are available:

| Property      | Type | Description                              |
|---------------|------|------------------------------------------|
| `loadPercent` | int  | Current UPS load as a percentage         |
| `runtime`     | int  | Remaining battery runtime value          |

## Running Tests

```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit
```

The test suite uses a namespace-scoped stub for `shell_exec` so no real UPS or Nagios plugin is required.

## Author

**Fred Bradley**
- Email: frb@cranleigh.org
- Website: http://www.fredbradley.uk
- GitHub: https://github.com/fredbradley/CheckSNMPClass

## License

MIT
