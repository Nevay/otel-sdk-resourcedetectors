<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Composer\InstalledVersions;
use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;
use function class_exists;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/#telemetry-sdk
 */
final class Sdk implements ResourceDetector {

    public function __construct(
        private readonly string $packageName = 'tbachert/otel-sdk',
    ) {}

    public function getResource(): Resource {
        $sdk = [];
        $sdk['telemetry.sdk.name'] = $this->packageName;
        $sdk['telemetry.sdk.language'] = 'php';
        $sdk['telemetry.sdk.version'] = 'unknown';
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($sdk['telemetry.sdk.name'])) {
            $sdk['telemetry.sdk.version'] = InstalledVersions::getVersionRanges($sdk['telemetry.sdk.name']);
        }
        if (($autoVersion = phpversion('opentelemetry')) !== false) {
            $sdk['telemetry.distro.name'] = 'opentelemetry';
            $sdk['telemetry.distro.version'] = $autoVersion;
        }

        return new Resource(
            new Attributes($sdk),
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }
}
