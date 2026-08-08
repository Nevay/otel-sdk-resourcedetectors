<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Entity;
use Nevay\OTelSDK\Common\Priority;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;

final class Service implements ResourceDetector {

    public function getResource(): Resource {
        $resource = Resource::create();

        if (($serviceName = $_SERVER['OTEL_SERVICE_NAME'] ?? null) !== null) {
            $resource = $resource->withEntity(new Entity(
                type: 'service',
                identity: ['service.name' => $serviceName],
                schemaUrl: 'https://opentelemetry.io/schemas/1.43.0',
            ));
        }

        return $resource;
    }
}
