<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/deployment-environment/
 */
final class Deployment implements ResourceDetector {

    public function getResource(): Resource {
        $deployment = [];
        if (($environment = $_SERVER['APP_ENV'] ?? '') !== '') {
            $deployment['deployment.environment'] = $environment;
        }

        return new Resource(
            new Attributes($deployment),
            schemaUrl: 'https://opentelemetry.io/schemas/1.34.0',
        );
    }
}
