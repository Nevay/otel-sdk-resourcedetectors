<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Nevay\OtelSDK\Common\ResourceDetector;

/**
 * Returns the default resource detector.
 *
 * @param ResourceDetector ...$additionalResourceDetectors additional resource
 *        detectors to use
 * @return ResourceDetector default resource detector
 */
function defaultResourceDetector(ResourceDetector ...$additionalResourceDetectors): ResourceDetector {
    return new ResourceDetector\Composite(...[
        new ResourceDetector\Env(),
        new ResourceDetector\Deployment(),

        ...$additionalResourceDetectors,

        new ResourceDetector\Composer(),
        new ResourceDetector\Host(),
        new ResourceDetector\Os(),
        new ResourceDetector\Process(),
        new ResourceDetector\ProcessRuntime(),
        new ResourceDetector\Sdk(),
        new ResourceDetector\SdkProvided(),
    ]);
}
