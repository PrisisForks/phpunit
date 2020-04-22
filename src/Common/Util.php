<?php

declare(strict_types=1);

/**
 * Copyright (c) 2020 Daniel Bannert
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/testomat/phpunit
 */

namespace Testomat\PHPUnit\Common;

use Closure;
use PHPUnit\Runner\Version;
use Testomat\PHPUnit\Common\Configuration\Configuration;
use Testomat\PHPUnit\Common\Configuration\Loader;
use Testomat\PHPUnit\Common\Contract\Exception\RuntimeException;
use Throwable;

final class Util
{
    /** @var null|array<string, mixed> */
    private static $phpunitArgumentsCache;

    /** @var null|\PHPUnit\TextUI\Configuration\Configuration|\PHPUnit\Util\Configuration */
    private static $phpunitConfigurationCache;

    /** @var null|Configuration */
    private static $testomatConfigurationCache;

    /**
     * Returns a callback that can read private variables from object.
     */
    public static function getGenericPropertyReader(): Closure
    {
        return function &(object $object, string $property) {
            $value = &Closure::bind(function &() use ($property) {
                return $this->{$property};
            }, $object, $object)->__invoke();

            return $value;
        };
    }

    public static function getPHPUnitTestRunnerArguments(): array
    {
        if (self::$phpunitArgumentsCache !== null) {
            return self::$phpunitArgumentsCache;
        }

        foreach (debug_backtrace() as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof \PHPUnit\TextUI\TestRunner) {
                return self::$phpunitArgumentsCache = $trace['args'][1];
            }
        }

        throw new RuntimeException('.');
    }

    /**
     * @throws \Testomat\PHPUnit\Common\Contract\Exception\RuntimeException if loading of the phpunit.xml failed
     *
     * @return \PHPUnit\TextUI\Configuration\Configuration|\PHPUnit\Util\Configuration
     */
    public static function getPHPUnitConfiguration()
    {
        if (self::$phpunitConfigurationCache !== null) {
            return self::$phpunitConfigurationCache;
        }

        $arguments = self::getPHPUnitTestRunnerArguments();
        /** @var \PHPUnit\Util\Configuration $configuration */
        $configuration = $arguments['configuration'] ?? null;

        if ($configuration === null) {
            throw new RuntimeException('@todo.');
        }

        return self::$phpunitConfigurationCache = $configuration;
    }

    public static function getTestomatConfiguration(): Configuration
    {
        if (self::$testomatConfigurationCache !== null) {
            return self::$testomatConfigurationCache;
        }

        $loader = new Loader();

        try {
            if ((int) (substr(Version::id(), 0, 1)) === 8) {
                $configurationPath = self::getPHPUnitConfiguration()->getFilename();
            } else {
                $configurationPath = self::getPHPUnitConfiguration()->filename();
            }

            $dir = \dirname($configurationPath);

            $configFile = $dir . \DIRECTORY_SEPARATOR . 'testomat.xml';

            if (! is_file($configFile)) {
                $configFile = $dir . \DIRECTORY_SEPARATOR . 'testomat.xml.dist';
            }

            $configuration = $loader->load($configFile);
        } catch (Throwable $exception) {
            echo 'Loading default configuration for testomat' . \PHP_EOL;

            $configuration = $loader->load(__DIR__ . \DIRECTORY_SEPARATOR . 'testomat.xml');
        }

        return self::$testomatConfigurationCache = $configuration;
    }

    public static function getPreparedTimeString(float $time): string
    {
        $timeString = Timer::secondsToTimeString($time);

        $space = ' ';

        if (strpos($timeString, 'ms') !== false) {
            $space = '   ';

            if ((int) filter_var($timeString, \FILTER_SANITIZE_NUMBER_INT) >= 10) {
                $space = '  ';
            }
        } elseif (strpos($timeString, 'sec') !== false) {
            $space = '  ';

            if ((int) filter_var($timeString, \FILTER_SANITIZE_NUMBER_INT) >= 10) {
                $space = ' ';
            }
        }

        return sprintf('  [%s]%s', $timeString, $space);
    }
}