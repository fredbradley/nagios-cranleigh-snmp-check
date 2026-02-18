<?php declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Shadow the global shell_exec inside the package namespace.
 *
 * PHP resolves unqualified function calls by looking in the current namespace
 * first, then falling back to the global namespace. Defining shell_exec here
 * means every call to shell_exec() inside CranleighSNMPCheck will hit this
 * stub instead of the real binary — no mocking library required.
 *
 * Tests set $GLOBALS['__snmp_fixture'] to control the return value.
 */
namespace FredBradley\CranleighSNMP {
    function shell_exec(string $command): ?string
    {
        return $GLOBALS['__snmp_fixture'] ?? null;
    }
}
