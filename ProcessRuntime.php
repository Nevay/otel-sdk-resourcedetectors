<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use const PHP_SAPI;
use const PHP_VERSION;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/process/#process-runtimes
 */
final class ProcessRuntime implements ResourceDetector {

    public function getResource(): Resource {
        $processRuntime = [
            'process.runtime.name' => PHP_SAPI,
            'process.runtime.version' => PHP_VERSION,
        ];

        return new Resource(
            new Attributes($processRuntime),
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }
}
