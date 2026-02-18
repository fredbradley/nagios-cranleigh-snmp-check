# Cranleigh SNMP Check

A PHP wrapper class for checking APC UPS devices via SNMP, designed for use with Nagios / nagDash dashboards.

[![Latest Stable Version](https://poser.pugx.org/fredbradley/cranleigh-snmp-check/v/stable)](https://packagist.org/packages/fredbradley/cranleigh-snmp-check)
[![License](https://poser.pugx.org/fredbradley/cranleigh-snmp-check/license)](https://packagist.org/packages/fredbradley/cranleigh-snmp-check)

## Requirements

- PHP >= 5.6
- The `check_snmp_apcups` Nagios plugin installed on your server (default path: `/usr/lib64/nagios/plugins/check_snmp_apcups`)
- SNMP access to your UPS device

## Installation

Install via Composer:

```bash
composer require fredbradley/cranleigh-snmp-check
```

## Usage

```php
<?php

require 'vendor/autoload.php';

use FredBradley\CranleighSNMP\CranleighSNMPCheck;

// Pass the IP address of your UPS and (optionally) the SNMP community name
$ups = new CranleighSNMPCheck('192.168.1.100', 'public');

// Output an HTML block suitable for use in a nagDash dashboard
echo $ups->displayBlock();
```

The `displayBlock()` method returns an HTML `<div>` with CSS classes reflecting the current Nagios state:

| State    | CSS class |
|----------|-----------|
| OK       | `ok`      |
| Warning  | `warning` |
| Critical | `notok`   |
| Unknown  | `unknown` |

Warning is triggered when load exceeds 70% or runtime drops below 30 minutes. Critical is triggered when load exceeds 80% or runtime drops below 20 minutes.

## Public Properties

After instantiation, the following properties are available:

| Property       | Type   | Description                              |
|----------------|--------|------------------------------------------|
| `load_percent` | int    | Current UPS load as a percentage         |
| `runtime`      | string | Remaining battery runtime value          |
| `css`          | object | CSS class names for each Nagios state    |

## Configuration

By default, the class calls the plugin at `/usr/lib64/nagios/plugins/check_snmp_apcups`. If your plugin lives elsewhere, you can change the `$check_command` property by extending the class:

```php
class MyUPSCheck extends \FredBradley\CranleighSNMP\CranleighSNMPCheck
{
    protected $check_command = '/usr/local/nagios/plugins/check_snmp_apcups';
}
```

> **Note:** You may need to change `$check_command` from `private` to `protected` in the source to allow this.

## Author

**Fred Bradley**
- Email: frb@cranleigh.org
- Website: http://www.fredbradley.uk
- GitHub: https://github.com/fredbradley/CheckSNMPClass

## License

MIT
