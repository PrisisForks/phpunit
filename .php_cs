<?php

declare(strict_types=1);

use Ergebnis\License;
use Narrowspark\CS\Config\Config;

$license = License\Type\MIT::markdown(
    __DIR__ . '/LICENSE.md',
    License\Range::since(
        License\Year::fromString('2020'),
        new \DateTimeZone('UTC')
    ),
    License\Holder::fromString('Daniel Bannert'),
    License\Url::fromString('https://github.com/testomat/phpunit')
);

$license->save();

$config = new Config($license->header(), [
    // @todo waiting for php-cs-fixer 2.16.2
    'global_namespace_import' => [
        'import_classes' => true,
        'import_constants' => false,
        'import_functions' => false,
    ],
    'final_public_method_for_abstract_class' => false
]);

$config->getFinder()
    ->files()
    ->in(__DIR__)
    ->exclude([
        '.build',
        '.dependabot',
        '.docker',
        '.github',
        'vendor',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config->setCacheFile(__DIR__ . '/.build/php-cs-fixer/.php_cs.cache');

return $config;
