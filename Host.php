<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Common\ResourceDetector;

use Amp\ByteStream;
use Amp\ByteStream\BufferException;
use Amp\Process\Process;
use Amp\Process\ProcessException;
use Nevay\OtelSDK\Common\Attributes;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Common\ResourceDetector;
use function fopen;
use function php_uname;
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
        try {
            $process = Process::start($command);
            $content = Bytestream\buffer($process->getStdout());
            if ($process->join() === 0) {
                return $content;
            }
        } catch (ProcessException | BufferException) {}

        return null;
    }

    private static function read(string $file): ?string {
        try {
            if ($handle = @fopen($file, 'rb')) {
                $stream = new ByteStream\ReadableResourceStream($handle);
                return ByteStream\buffer($stream);
            }
        } catch (ByteStream\StreamException) {}

        return null;
    }
}
