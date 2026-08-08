<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Entity;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/#telemetry-sdk
 */
final class Extension implements ResourceDetector {

    public function getResource(): Resource {
        $resource = Resource::create();

        if (($autoVersion = phpversion('opentelemetry')) !== false) {
            $resource = $resource->withEntity(new Entity(
                type: 'telemetry.distro',
                identity: ['telemetry.distro.name' => 'opentelemetry'],
                description: ['telemetry.distro.version' => $autoVersion],
                schemaUrl: 'https://opentelemetry.io/schemas/1.43.0',
            ));
        }

        return $resource;
    }
}
