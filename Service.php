<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;

final class Service implements ResourceDetector {

    public function getResource(): Resource {
        $service = [];
        if (($serviceName = $_SERVER['OTEL_SERVICE_NAME'] ?? null) !== null) {
            $service['service.name'] = $serviceName;
        }

        return new Resource(
            new Attributes($service),
            schemaUrl: 'https://opentelemetry.io/schemas/1.34.0',
        );
    }
}
