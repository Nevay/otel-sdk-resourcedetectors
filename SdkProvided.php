<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/#semantic-attributes-with-sdk-provided-default-value
 */
final class SdkProvided implements ResourceDetector {

    public function getResource(): Resource {
        $sdkProvided = [
            'service.name' => 'unknown_service:php',
        ];

        return new Resource(
            new Attributes($sdkProvided),
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }
}
