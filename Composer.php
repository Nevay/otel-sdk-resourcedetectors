<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Composer\InstalledVersions;
use Nevay\OTelSDK\Common\Entity;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/#service
 */
final class Composer implements ResourceDetector {

    public function getResource(): Resource {
        $resource = Resource::create();

        $package = InstalledVersions::getRootPackage();
        if ($package['name'] !== '__root__') {
            $service = new Entity(
                type: 'service',
                identity: ['service.name' => $package['name']],
                schemaUrl: 'https://opentelemetry.io/schemas/1.43.0',
            );

            if ($package['pretty_version'] !== '1.0.0+no-version-set') {
                $service->description['service.version'] = $package['pretty_version'];
            }

            $resource = $resource->withEntity($service);
        }

        return $resource;
    }
}
