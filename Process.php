<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use function array_pop;
use function basename;
use function explode;
use function extension_loaded;
use function file_get_contents;
use function getmypid;
use function restore_error_handler;
use function set_error_handler;
use const PHP_BINARY;
use const PHP_OS_FAMILY;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/process/#process
 */
final class Process implements ResourceDetector {

    public function getResource(): Resource {
        $process = [];
        $process['process.pid'] = getmypid();
        if (extension_loaded('posix')) {
            $process['process.parent_pid'] = \posix_getppid();
        }

        $commandLine = self::commandLine();
        $process['process.executable.name'] = basename($commandLine[0]);
        $process['process.executable.path'] = PHP_BINARY;
        $process['process.command'] = $commandLine[0];
        $process['process.command_args'] = $commandLine;

        if (extension_loaded('posix') && ($user = \posix_getpwuid(\posix_geteuid())) !== false) {
            $process['process.owner'] = $user['name'];
        }

        return new Resource(
            new Attributes($process),
            schemaUrl: 'https://opentelemetry.io/schemas/1.25.0',
        );
    }

    private static function commandLine(): array {
        if (PHP_OS_FAMILY === 'Linux') {
            set_error_handler(static fn() => null);
            try {
                if (($content = file_get_contents('/proc/self/cmdline')) !== false) {
                    $parsed = explode("\0", $content);
                    array_pop($parsed);
                    if ($parsed) {
                        return $parsed;
                    }
                }
            } finally {
                restore_error_handler();
            }
        }

        return [PHP_BINARY, ...$_SERVER['argv']];
    }
}
