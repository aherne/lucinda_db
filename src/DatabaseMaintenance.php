<?php

namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\ByTag as DeleteTag;
use Lucinda\DB\FileDeleter\ByModifiedTime as DeleteByModifiedTime;
use Lucinda\DB\FileDeleter\ByCapacity as DeleteByCapacity;

/**
 * Maintains replica membership and removes entries using a persistent XML file
 *
 * Unlike Wrapper, this operation needs a filename because schema membership edits
 * must be saved. It reads lucinda_db/schemas/schema directly, without selecting an
 * environment, and refreshes its Configuration after each membership change.
 *
 * plugIn() copies missing entries from the first replica before and after XML
 * registration. plugOut() unregisters a replica before clearing its entry files;
 * the directory remains. Removing the final configured schema is rejected before
 * XML or entries are changed. addSchema() and removeSchema() expose only the XML
 * steps and do not copy or clear data.
 *
 * Membership changes are separate filesystem operations, without transaction or
 * configuration-file locking. Other wrappers retain their own schema snapshots.
 *
 * @see Configuration
 * @see Schema
 * @see SchemaStatus
 */
final class DatabaseMaintenance
{
    /**
     * XML filename used for persistent membership edits
     */
    private string $xmlFilePath;

    /**
     * Current schema snapshot, refreshed after each membership edit
     */
    private Configuration $configuration;

    /**
     * Loads the schema list and retains the filename for later membership edits
     *
     * @param string $xmlFilePath Existing XML configuration filename, including extension
     * @throws ConfigurationException If the file is absent, unreadable, malformed, or lacks schemas
     */
    public function __construct(string $xmlFilePath)
    {
        $this->configuration = $this->getConfiguration($xmlFilePath);
        $this->xmlFilePath = $xmlFilePath;
    }

    /**
     * Loads and validates an environment-independent configuration file
     *
     * SimpleXML may also emit parser warnings for malformed input.
     *
     * @param string $xmlFilePath Configuration filename to read
     * @return Configuration New snapshot of the file's replica list
     * @throws ConfigurationException If the file cannot supply valid database configuration
     */
    private function getConfiguration(string $xmlFilePath): Configuration
    {
        if (!file_exists($xmlFilePath)) {
            throw new ConfigurationException("XML file doesn't exist: ".$xmlFilePath);
        }
        $xml = \simplexml_load_file($xmlFilePath);
        if (empty($xml)) {
            throw new ConfigurationException("XML file is not readable: ".$xmlFilePath);
        }
        return new Configuration($xml);
    }

    /**
     * Probes each configured schema and reports its observed health
     *
     * Missing or unwritable directories are OFFLINE. Other schemas receive a temporary
     * __test__ file which is read and removed. The measured duration covers the write;
     * a successful write exceeding the threshold is OVERLOADED. I/O exceptions propagate
     * rather than being converted into a status.
     *
     * @param float $maximumWriteDuration Maximum acceptable write time in seconds, including fractions
     * @return array<string,SchemaStatus> Status keyed by configured schema path
     * @throws \JsonException If probe data cannot be decoded
     * @throws LockException If a probe mutation cannot acquire its mutex
     * @throws \RuntimeException If a probe write cannot replace its file
     */
    public function checkHealth(float $maximumWriteDuration): array
    {
        $output = [];
        $schemas = $this->configuration->getSchemas();
        foreach ($schemas as $schema) {
            $object = new Schema($schema);
            if (!$object->exists()) {
                $output[$schema] = SchemaStatus::OFFLINE;
            } else {
                $object = new File($schema.DIRECTORY_SEPARATOR."__test__");
                $start = microtime(true);
                $object->write("asd");
                $end = microtime(true);
                if (!$object->exists() || $object->read()!="asd") {
                    $output[$schema] = SchemaStatus::UNRESPONSIVE;
                } elseif (($end-$start) > $maximumWriteDuration) {
                    $output[$schema] = SchemaStatus::OVERLOADED;
                } else {
                    $output[$schema] = SchemaStatus::ONLINE;
                }
                $object->delete();
            }
        }
        return $output;
    }

    /**
     * Imports and registers an existing writable replica folder
     *
     * Copies missing entries from the first configured replica, persists membership,
     * then copies again to catch entries created during registration. Existing files
     * in the destination are retained, including their values. Imports preserve mtimes.
     *
     * @param string $schema Existing writable directory to add
     * @throws ConfigurationException If the directory is unavailable, already registered, or configuration reload fails
     */
    public function plugIn(string $schema): void
    {
        $object = new Schema($schema);

        if (!$object->exists()) {
            throw new ConfigurationException("Schema to plug in not found: ".$schema);
        }

        if (in_array($schema, $this->configuration->getSchemas())) {
            throw new ConfigurationException("Schema already plugged!");
        }


        // import files into new schema
        $object->populate($this->configuration->getSchemas()[0]);

        // plug in schema in XML
        $this->addSchema($schema);

        // import any remaining files that may have been inserted by different processes as steps above unfolded
        $object->populate($this->configuration->getSchemas()[0]);
    }

    /**
     * Unregisters a replica and deletes its database entries
     *
     * Membership is saved before entry removal. The folder itself remains available
     * for reuse. The final configured schema cannot be removed.
     *
     * @param string $schema Existing writable directory currently registered
     * @throws ConfigurationException If the schema is unavailable, unregistered, the last replica, or reload fails
     */
    public function plugOut(string $schema): void
    {
        $object = new Schema($schema);

        if (!$object->exists()) {
            throw new ConfigurationException("Schema to plug out not found: ".$schema);
        }

        if (!in_array($schema, $this->configuration->getSchemas())) {
            throw new ConfigurationException("Schema not plugged!");
        }

        // plug out schema in XML
        $this->removeSchema($schema);

        // clear schema of files
        $object->deleteAll();
    }

    /**
     * Deletes matching keys across replicas by scanning one random schema
     *
     * Matches complete underscore-separated tag segments. The count represents
     * matching entries in the scanned schema, rather than physical replica files.
     *
     * @param string $tag Lowercase tag to match within entry keys
     * @return int Number of matching scanned entries removed
     * @throws KeyException If the tag contains unsupported characters
     */
    public function deleteByTag(string $tag): int
    {
        $schemas = $this->configuration->getSchemas();
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        return $folder->clear(new DeleteTag($tag, $schemas));
    }

    /**
     * Deletes entries older than the specified age across configured replicas
     *
     * Uses modification times in one randomly selected schema and removes matching
     * filenames from every replica. Files at the cutoff timestamp are retained.
     *
     * @param int $secondsBeforeNow Age in seconds subtracted from the current Unix time
     * @return int Number of matching entries found in the scanned schema
     */
    public function deleteUntil(int $secondsBeforeNow): int
    {
        $schemas = $this->configuration->getSchemas();
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        return $folder->clear(new DeleteByModifiedTime(time()-$secondsBeforeNow, $schemas));
    }

    /**
     * Evicts older entries when the scanned population reaches a threshold
     *
     * Feeds entries from one random schema to a bounded timestamp heap. Each time the
     * heap reaches maxCapacity it discards the oldest entries down to minCapacity,
     * removing their filenames from all configured replicas.
     *
     * @param int $minCapacity Entries retained after each threshold-triggered eviction
     * @param int $maxCapacity Heap size triggering eviction; use 0 <= minCapacity < maxCapacity
     * @return int Number of logical entries evicted across all replicas
     */
    public function deleteByCapacity(int $minCapacity, int $maxCapacity): int
    {
        $schemas = $this->configuration->getSchemas();
        $fileDeleter = new DeleteByCapacity($schemas, $minCapacity, $maxCapacity);
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        $folder->clear($fileDeleter);
        return $fileDeleter->getTotal();
    }

    /**
     * Appends a schema path to XML and refreshes this instance's configuration
     *
     * Formats the saved XML using DOM. This low-level operation performs no folder
     * validation, duplicate check, or data import; use plugIn() for those steps.
     *
     * @param string $schema Schema path to append to lucinda_db/schemas
     * @throws ConfigurationException If the saved configuration cannot be reloaded
     */
    public function addSchema(string $schema): void
    {
        $xmlRoot = \simplexml_load_file($this->xmlFilePath);
        $parent = $xmlRoot->lucinda_db->schemas;
        $parent->addChild("schema", $schema);

        // beautify xml
        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xmlRoot->asXML());
        $domxml->save($this->xmlFilePath);

        // refresh configuration
        $this->configuration = $this->getConfiguration($this->xmlFilePath);
    }

    /**
     * Removes the first matching XML schema and refreshes cached configuration
     *
     * Rejects removal while only one schema is configured, before changing the file.
     * Entry files and directories remain untouched; use plugOut() to clear entries too.
     *
     * @param string $schema Configured path to match exactly
     * @throws ConfigurationException If only one schema remains or configuration reload fails
     */
    public function removeSchema(string $schema): void
    {
        $xmlRoot = \simplexml_load_file($this->xmlFilePath);
        $parent = $xmlRoot->lucinda_db->schemas;
        if (sizeof($parent->schema) === 1) {
            throw new ConfigurationException("Remove would potentially empty schemas: ".$this->xmlFilePath);
        }
        foreach ($parent->schema as $child) {
            if ((string) $child == $schema) {
                $dom=dom_import_simplexml($child);
                $dom->parentNode->removeChild($dom);
                break;
            }
        }
        $xmlRoot->asXML($this->xmlFilePath);

        // refresh configuration
        $this->configuration = $this->getConfiguration($this->xmlFilePath);
    }
}
