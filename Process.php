<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use DateTimeImmutable;
use Nevay\OTelSDK\Common\Entity;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use function array_pop;
use function basename;
use function cli_get_process_title;
use function explode;
use function extension_loaded;
use function file_get_contents;
use function function_exists;
use function getcwd;
use function getmypid;
use function restore_error_handler;
use function set_error_handler;
use function trim;
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
        $resource = Resource::create();

        if ($requestTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? null) {
            $process = new Entity(
                type: 'process',
                identity: [
                    'process.creation.time' => DateTimeImmutable::createFromFormat('U.u', (string) $requestTime)->format('Y-m-d\TH:i:s.vp'),
                    'process.pid' => getmypid(),
                ],
                schemaUrl: 'https://opentelemetry.io/schemas/1.43.0',
            );

            $commandLine = self::commandLine();
            $process->description['process.executable.name'] = basename(PHP_BINARY);
            $process->description['process.executable.path'] = PHP_BINARY;
            $process->description['process.command'] = $commandLine[0];
            $process->description['process.command_args'] = $commandLine;
            $process->description['process.args_count'] = count($commandLine);

            if (extension_loaded('posix') && ($user = \posix_getpwuid(\posix_geteuid())) !== false) {
                $process->description['process.owner'] = $user['name'];
            }
            if (extension_loaded('posix')) {
                $process->description['process.interactive'] = \posix_isatty(0);
            }
            if (PHP_OS_FAMILY === 'Linux' && ($cgroups = self::read('/proc/self/cgroup')) !== null) {
                $process->description['process.linux.cgroup'] = trim($cgroups);
            }
            if (($cwd = getcwd()) !== false) {
                $process->description['process.working_directory'] = $cwd;
            }

            $resource = $resource->withEntity($process);
        }

        $resource = $resource->withEntity(new Entity(
            type: 'process.runtime',
            identity: [
                'process.runtime.name' => PHP_SAPI,
                'process.runtime.version' => PHP_VERSION,
            ],
            schemaUrl: 'https://opentelemetry.io/schemas/1.43.0',
        ));

        return $resource;
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
