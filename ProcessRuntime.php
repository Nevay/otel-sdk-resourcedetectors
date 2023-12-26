<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;
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
