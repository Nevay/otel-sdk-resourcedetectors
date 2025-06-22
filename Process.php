<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use function array_pop;
use function basename;
use function cli_get_process_title;
use function explode;
use function extension_loaded;
use function file_get_contents;
use function function_exists;
use function getmypid;
use function restore_error_handler;
use function set_error_handler;
use const PHP_BINARY;
use const PHP_OS_FAMILY;
use const PHP_SAPI;
use const PHP_VERSION;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/process/#process
 * @see https://opentelemetry.io/docs/specs/semconv/resource/process/#process-runtimes
 */
final class Process implements ResourceDetector {

    public function getResource(): Resource {
        $process = [];
        $process['process.pid'] = getmypid();
        if (extension_loaded('posix')) {
            $process['process.parent_pid'] = \posix_getppid();
        }

        $commandLine = self::commandLine();
        $process['process.executable.name'] = basename(PHP_BINARY);
        $process['process.executable.path'] = PHP_BINARY;
        $process['process.command'] = $commandLine[0];
        $process['process.command_args'] = $commandLine;

        if (extension_loaded('posix') && ($user = \posix_getpwuid(\posix_geteuid())) !== false) {
            $process['process.owner'] = $user['name'];
        }

        $process['process.runtime.name'] = PHP_SAPI;
        $process['process.runtime.version'] = PHP_VERSION;

        return new Resource(
            new Attributes($process),
            schemaUrl: 'https://opentelemetry.io/schemas/1.34.0',
        );
    }

    private static function commandLine(): array {
        if (PHP_OS_FAMILY === 'Linux' && (!function_exists('cli_get_process_title') || @cli_get_process_title() === '')) {
            if (($content = self::read('/proc/self/cmdline')) !== null) {
                $parsed = explode("\0", $content);
                array_pop($parsed);
                if ($parsed) {
                    return $parsed;
                }
            }
        }

        return [PHP_BINARY, ...$_SERVER['argv'] ?? []];
    }

    private static function read(string $file): ?string {
        set_error_handler(static fn() => null);
        try {
            if (($content = file_get_contents($file)) !== false) {
                return $content;
            }
        } finally {
            restore_error_handler();
        }

        return null;
    }
}
