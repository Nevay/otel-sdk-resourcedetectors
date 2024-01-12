<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;
use function file_get_contents;
use function php_uname;
use function restore_error_handler;
use function set_error_handler;
use function shell_exec;
use function trim;

/**
 * @see https://opentelemetry.io/docs/specs/semconv/resource/host/
 */
final class Host implements ResourceDetector {

    public function getResource(): Resource {
        $host = [];
        if (($hostId = $this->rawHostId()) !== null) {
            $host['host.id'] = trim($hostId);
        }
        $host['host.name'] = php_uname('n');
        $host['host.arch'] = php_uname('m');

        return new Resource(
            new Attributes($host),
            schemaUrl: 'https://opentelemetry.io/schemas/1.24.0',
        );
    }

    private function rawHostId(): ?string {
        /** @noinspection SpellCheckingInspection */
        return match (PHP_OS_FAMILY) {
            'Linux'
                => self::read('/etc/machine-id')
                ?? self::read('/var/lib/dbus/machine-id'),
            'BSD'
                => self::read('/etc/hostid')
                ?? self::exec(<<<'CMD'
                    kenv -q smbios.system.uuid
                    CMD),
            'Darwin'
                => self::exec(<<<'CMD'
                    ioreg -d2 -c IOPlatformExpertDevice | awk -F\" '/IOPlatformUUID/{print $(NF-1)}'
                    CMD),
            'Windows'
                => self::exec(<<<'CMD'
                    powershell.exe -Command (Get-ItemProperty -Path HKLM:\SOFTWARE\Microsoft\Cryptography -Name MachineGuid).MachineGuid
                    CMD),
            default => null,
        };
    }

    private static function exec(string $command): ?string {
        set_error_handler(static fn() => null);
        try {
            if (($output = shell_exec($command)) !== false) {
                return $output;
            }
        } finally {
            restore_error_handler();
        }

        return null;
    }

    private static function read(string $file): ?string {
        set_error_handler(static fn() => null);
        try {
            if (($content = file_get_contents($file)) !== false) {
                return $content;
            }
        } finally {
            restore_error_handler();
        }

        return null;
    }
}
