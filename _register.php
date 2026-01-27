<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\ResourceDetector;
use Nevay\SPI\ServiceLoader;
use function class_exists;

if (!class_exists(ServiceLoader::class)) {
    return;
}

ServiceLoader::register(ResourceDetector::class, Composer::class);
ServiceLoader::register(ResourceDetector::class, Container::class);
ServiceLoader::register(ResourceDetector::class, Deployment::class);
ServiceLoader::register(ResourceDetector::class, Extension::class);
ServiceLoader::register(ResourceDetector::class, Host::class);
ServiceLoader::register(ResourceDetector::class, Process::class);
ServiceLoader::register(ResourceDetector::class, Service::class);
