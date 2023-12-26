<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;

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
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }
}
