<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Amp\ByteStream;
use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;
use function basename;
use function extension_loaded;
use function fopen;
use function getmypid;
use function iterator_to_array;
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
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }

    private static function commandLine(): array {
        if (PHP_OS_FAMILY === 'Linux') {
            try {
                if ($handle = @fopen('/proc/self/cmdline', 'rb')) {
                    $stream = new ByteStream\ReadableResourceStream($handle);
                    if ($parsed = iterator_to_array(ByteStream\split($stream, "\0"))) {
                        return $parsed;
                    }
                }
            } catch (ByteStream\StreamException) {}
        }

        return [PHP_BINARY, ...$_SERVER['argv']];
    }
}
