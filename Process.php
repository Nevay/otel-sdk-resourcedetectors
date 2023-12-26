<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;
use function basename;
use function extension_loaded;
use function getmypid;
use const PHP_BINARY;

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
        $process['process.executable.name'] = basename(PHP_BINARY);
        $process['process.executable.path'] = PHP_BINARY;
        if ($_SERVER['argv'] ?? null) {
            $process['process.command'] = $_SERVER['argv'][0];
            $process['process.command_args'] = $_SERVER['argv'];
        }
        if (extension_loaded('posix') && ($user = \posix_getpwuid(\posix_geteuid())) !== false) {
            $process['process.owner'] = $user['name'];
        }

        return new Resource(
            new Attributes($process),
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }
}
