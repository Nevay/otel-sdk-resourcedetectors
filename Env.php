<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;
use function count;
use function explode;
use function rawurldecode;
use function trim;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/#semantic-attributes-with-dedicated-environment-variable
 */
final class Env implements ResourceDetector {

    public function getResource(): Resource {
        $env = [];
        if (($resourceAttributes = $_SERVER['OTEL_RESOURCE_ATTRIBUTES'] ?? '') !== '') {
            foreach (explode(',', $resourceAttributes) as $attribute) {
                $member = explode('=', $attribute, 2);
                if (count($member) !== 2) {
                    continue;
                }

                $key = trim($member[0], " \t");
                $val = trim($member[1], " \t");

                $env[$key] = rawurldecode($val);
            }
        }
        if (($serviceName = $_SERVER['OTEL_SERVICE_NAME'] ?? '') !== '') {
            $env['service.name'] = $serviceName;
        }

        return new Resource(
            new Attributes($env),
        );
    }
}
