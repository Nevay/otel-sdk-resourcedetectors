<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use function fgets;
use function fopen;
use function preg_match;
use function restore_error_handler;
use function set_error_handler;
use function str_contains;

final class Container implements ResourceDetector {

    public function getResource(): Resource {
        $container = [];

        if ($containerId = self::cgroupV2()) {
            $container['container.id'] ??= $containerId;
        }

        return new Resource(
            new Attributes($container),
            schemaUrl: 'https://opentelemetry.io/schemas/1.34.0',
        );
    }

    private static function cgroupV2(): ?string {
        foreach (self::read('/proc/self/mountinfo') as $line) {
            if (!str_contains($line, 'hostname')) {
                continue;
            }

            if (preg_match('~/(?<id>[[:xdigit:]]{64})/~', $line, $matches)) {
                return $matches['id'];
            }
        }

        return null;
    }

    private static function read(string $file): iterable {
        set_error_handler(static fn() => null);
        try {
            $h = fopen($file, 'rb');
            while (($line = fgets($h)) !== false) {
                yield $line;
            }
        } finally {
            restore_error_handler();
        }

        return null;
    }
}
