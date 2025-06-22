<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/#telemetry-sdk
 */
final class Extension implements ResourceDetector {

    public function getResource(): Resource {
        $ext = [];
        if (($autoVersion = phpversion('opentelemetry')) !== false) {
            $ext['telemetry.distro.name'] = 'opentelemetry';
            $ext['telemetry.distro.version'] = $autoVersion;
        }

        return new Resource(
            new Attributes($ext),
            schemaUrl: 'https://opentelemetry.io/schemas/1.34.0',
        );
    }
}
