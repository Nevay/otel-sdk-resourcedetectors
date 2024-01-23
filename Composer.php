<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Composer\InstalledVersions;
use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use function class_exists;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/#service
 */
final class Composer implements ResourceDetector {

    public function getResource(): Resource {
        $composer = [];
        if (class_exists(InstalledVersions::class)) {
            $composer['service.name'] = InstalledVersions::getRootPackage()['name'];
            $composer['service.version'] = InstalledVersions::getRootPackage()['pretty_version'];

            if ($composer['service.name'] === '__root__') {
                unset($composer['service.name']);
            }
            if ($composer['service.version'] === '1.0.0+no-version-set') {
                unset($composer['service.version']);
            }
        }

        return new Resource(
            new Attributes($composer),
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }
}
