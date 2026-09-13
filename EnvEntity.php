<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Common\ResourceDetector;

use Nevay\OTelSDK\Common\Entity;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\ResourceDetector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function array_values;
use function explode;
use function filter_var;
use function preg_match;
use function rawurldecode;
use function strlen;
use function strpos;
use function substr;

/**
 * @see https://opentelemetry.io/docs/specs/otel/entities/entity-propagation/#specifying-entity-information-via-an-environment-variable
 *
 * @experimental
 */
final class EnvEntity implements ResourceDetector {

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getResource(): Resource {
        $raw = $_SERVER['OTEL_ENTITIES'] ?? '';
        if ($raw === '') {
            return Resource::create();
        }

        // 1. Split the input string by semicolons to get individual entity definitions.
        $entities = [];
        foreach (explode(';', $raw) as $definition) {
            // 2.1 Skip if the entity definition is empty (allows consecutive
            //     semicolons and leading/trailing semicolons).
            if ($definition === '') {
                continue;
            }

            if (null === ($entity = $this->parseEntity($definition))) {
                $this->logger->warning('OTEL_ENTITIES: ignoring malformed entity definition "' . $definition . '"');
                continue;
            }

            /*
             * Duplicate entities: keep the last occurrence per type, whether
             * the identifying attributes are identical or conflicting. Both
             * error-handling rules 3 and 6 mandate that the last definition
             * wins for attribute values, rule 5 additionally preserves only
             * the last entity, and the resource model keeps a single entity
             * reference per type after merging.
             */
            if (isset($entities[$entity->type])) {
                $this->logger->warning('OTEL_ENTITIES: duplicate entity of type "' . $entity->type . '"; using the last occurrence');
            }

            $entities[$entity->type] = $entity;
        }

        // 5. Create entity objects and associate them with the resource in a
        //    single merge pass
        if ($entities === []) {
            return Resource::create();
        }

        return Resource::create()->withEntity(...array_values($entities));
    }

    private function parseEntity(string $definition): ?Entity {
        // 2.2 Extract the entity type (everything before the first '{').
        if (false === ($open = strpos($definition, '{'))) {
            return null;
        }

        $type = substr($definition, 0, $open);
        if ('' === $type || !preg_match('/^[a-zA-Z][a-zA-Z0-9._-]*$/', $type)) {
            return null; // validation: non-empty type matching the grammar
        }

        // 2.3 Extract identifying attributes from the '{...}' block (required).
        if (false === ($close = strpos($definition, '}', $open))) {
            return null;
        }

        $identity = self::parseKeyValueList(substr($definition, $open + 1, $close - $open - 1));
        if ($identity === []) {
            return null; // validation: at least one identifying attribute
        }

        // Offset of the first character after the identity block.
        $offset = $close + 1;
        $length = strlen($definition);

        // 2.4 Extract descriptive attributes from the '[...]' block (if present).
        $description = [];
        if ($offset < $length && '[' === $definition[$offset]) {
            if (false === ($close = strpos($definition, ']', $offset))) {
                return null;
            }

            $description = self::parseKeyValueList(substr($definition, $offset + 1, $close - $offset - 1));
            $offset = $close + 1;
        }

        // 2.5 Extract schema URL from the '@...' portion (if present).
        $schemaUrl = null;
        if ($offset < $length) {
            if ('@' !== $definition[$offset]) {
                return null; // trailing garbage
            }

            $url = substr($definition, $offset + 1);
            if (false === filter_var($url, \FILTER_VALIDATE_URL)) {
                /*
                 * Schema URL validation: an invalid URL is ignored with a
                 * warning while the entity itself is still processed.
                 */
                $this->logger->warning('OTEL_ENTITIES: ignoring invalid schema URL "' . $url . '" for entity type "' . $type . '"');
            } else {
                $schemaUrl = $url;
            }
        }

        return new Entity($type, $identity, $description, $schemaUrl);
    }

    /**
     * 3. Parses a key/value list using comma (',') as separator and equals
     *    ('=') for assignment; values are percent-decoded per the W3C Baggage
     *    specification.
     *
     * @return array<string, string> parsed key/value pairs, empty if malformed
     */
    private static function parseKeyValueList(string $list): array {
        $result = [];
        foreach (explode(',', $list) as $pair) {
            if ('' === $pair) {
                continue;
            }

            if (false === ($equals = strpos($pair, '='))) {
                return [];
            }

            $key = substr($pair, 0, $equals);
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9._-]*$/', $key)) {
                return [];
            }

            $result[$key] = rawurldecode(substr($pair, $equals + 1));
        }

        return $result;
    }
}
