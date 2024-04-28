<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use function php_uname;
use function strtolower;
use const PHP_OS;
use const PHP_OS_FAMILY;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/os/
 */
final class Os implements ResourceDetector {

    public function getResource(): Resource {
        $os = [
            'os.type' => strtolower(PHP_OS_FAMILY),
            'os.description' => php_uname(),
            'os.name' => PHP_OS,
            'os.version' => php_uname('r'),
        ];

        return new Resource(
            new Attributes($os),
            schemaUrl: 'https://opentelemetry.io/schemas/1.25.0',
        );
    }
}
