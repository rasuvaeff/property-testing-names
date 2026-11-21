<?php

declare(strict_types=1);

use Rasuvaeff\RectorNamedLiterals\AddNameToLiteralArgumentRector;
use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withRules([AddNameToLiteralArgumentRector::class])
    ->withSkip([
        // Rewrites `$gender === null` into `$gender instanceof Gender`,
        // dragging fully-qualified names into code that already reads clearly.
        // (Newer Rector releases do this rewrite here instead of in the deprecated FlipTypeControlToUseExclusiveTypeRector.)
        ExplicitNullableObjectInstanceRector::class,
    ]);
