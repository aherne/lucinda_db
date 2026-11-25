# Lucinda DB

Lucinda DB is a pure PHP, file-backed key-value store. Each JSON entry belongs to a key derived from tags and can be replicated across several schema folders. Applications supply parsed XML to the database wrapper; maintenance receives a filename so it can persist schema changes. Environment selection belongs to the calling application.

- [Requirements](#requirements)
- [Installation](#installation)
- [Storage model](#storage-model)
- [Configuration](#configuration)
- [Entry operations](#entry-operations)
- [Replication and locking](#replication-and-locking)
- [Schema operations](#schema-operations)
- [Maintenance](#maintenance)
- [Console client](#console-client)
- [Exceptions](#exceptions)
- [Constraints](#constraints)
- [Testing](#testing)

## Requirements

- PHP 8.1+
- SPL, SimpleXML, XML, and DOM extensions
- Composer autoloading
- Writable schema directories for entry operations and a writable configuration file for membership changes

Development tests use `lucinda/unit-testing`. The console integration tests also use PHP's `proc_open`.

## Installation

```console
composer require lucinda/db
```

Load Composer's autoloader before using the API:

```php
require __DIR__ . '/vendor/autoload.php';
```

Create a configuration document and its schema directories before writing entries. Schema folders can also be created with the schema driver shown below.

## Storage Model

| Concept | Input | Result |
| --- | --- | --- |
| Tags | Nonempty collection of strings containing lowercase ASCII letters, digits, or hyphens | Validated and sorted tags; duplicates are preserved |
| Key | Tags, in any order | Sorted tags joined with `_`, without an extension |
| Value | JSON-encodable PHP value | A `{key}.json` file in each configured schema |
| Schema | Directory path | One replica containing entry files |

```php
$key = new Lucinda\DB\Key(["users", "roles"]);
echo $key->getValue(); // roles_users
```

JSON objects decode as associative arrays. JSON serialization does not preserve arbitrary PHP object types.

## Configuration

The application document must contain `lucinda_db/schemas/schema` directly. At least one schema is required; there is no environment element:

```xml
<?xml version="1.0" encoding="utf-8"?>
<xml>
  <lucinda_db>
    <schemas>
      <schema>/var/app/db/schema1</schema>
      <schema>/var/app/db/schema2</schema>
    </schemas>
  </lucinda_db>
</xml>
```

`Configuration` and `Wrapper` receive the complete application root as a `SimpleXMLElement`, rather than the `lucinda_db` child or a filename:

```php
use Lucinda\DB\Configuration;
use Lucinda\DB\Wrapper;

$xml = simplexml_load_file(__DIR__ . '/configuration.xml');
if ($xml === false) {
    throw new RuntimeException('Cannot parse database configuration');
}

$configuration = new Configuration($xml);
$paths = $configuration->getSchemas(); // string[], in XML order
$db = new Wrapper($xml);
```

The caller loads XML and chooses any environment-specific file or document. Configuration retains paths exactly as supplied: relative paths resolve against PHP's current working directory. Absolute paths and consistent spelling make filesystem and lock behavior easier to coordinate.

The first schema is the source for imports and integer counter updates. Configuration reads a snapshot; it does not create directories, validate their availability, or persist changes. Schema registration methods belong to `DatabaseMaintenance`.

To migrate from the previous API, remove environment elements from XML, replace `new Configuration($filename, $environment)` and `new Wrapper($filename, $environment)` with constructors receiving parsed XML, and replace `new DatabaseMaintenance($filename, $environment)` with `new DatabaseMaintenance($filename)`.

## Entry Operations

`Wrapper::getEntryDriver(string[] $tags): ValueOperations` creates a `ValueDriver` bound to the canonical key and configured schemas. Its PHP parameter declaration is `array`; tags are validated during construction.

```php
$entry = $db->getEntryDriver(["users", "roles"]);
$entry->set(["Jane Doe" => ["Admin"]]);

if ($entry->exists()) {
    $data = $entry->get(); // ["Jane Doe" => ["Admin"]]
}
```

| Method | Inputs | Output and behavior |
| --- | --- | --- |
| `set(mixed $value): void` | JSON-encodable value | Creates or replaces the entry in every replica |
| `get(): mixed` | None | Decoded value from a successful replica read; attempts to repair other copies |
| `exists(): bool` | None | Whether any replica has the entry file; does not validate JSON |
| `increment(int $step = 1): int` | Amount to add to an existing integer-compatible value | Updated value from the first schema, copied to the others |
| `decrement(int $step = 1): int` | Amount to subtract from an existing integer-compatible value | Updated value from the first schema, copied to the others |
| `delete(): void` | None | Deletes the entry wherever it exists; throws if absent from every replica |

Use `Value` for direct access to one schema. Its key argument is an already generated filename stem:

```php
$key = new Lucinda\DB\Key(["users", "roles"]);
$entry = new Lucinda\DB\Value('/var/app/db/schema1', $key->getValue());
$entry->set(["Jane Doe" => ["Admin"]]);
```

Single-schema reads, counter updates, and deletion throw `KeyNotFoundException` when the file is absent. Counter methods expect integer-compatible data; they do not validate arbitrary stored JSON values as integers.

## Replication And Locking

`ValueDriver` coordinates replicas as follows:

- Writes visit every configured schema in order.
- Reads try schemas in randomized order until a value can be decoded. Missing entries and malformed JSON are skipped.
- A successful read attempts to replace missing, malformed, or differing replicas. Repair failures do not prevent that value from being returned.
- Counter updates modify the first schema under its entry mutex, then write the resulting value to the remaining schemas.
- Deletion skips missing replicas and removes existing copies.

A read uses the first decodable copy; it does not compare versions or elect a majority. If replicas contain differing valid values, the selected value can overwrite another copy during repair. If every read fails, the last attempted missing-key or JSON failure determines the exception.

`File` protects writes, updates, and deletion with a nonblocking exclusive mutex. Contention raises `LockException` immediately. The mutex filename is derived from the supplied entry path and stored in the system temporary directory; it remains after release. Use consistent path strings across processes addressing the same entry.

Writes create a temporary file beside the destination and rename it into place. `File::update(FileUpdater $callback)` holds the mutex across decoding, callback execution, and replacement. The callback receives the decoded value by reference and returns `true` to persist it or `false` to leave the file unchanged.

Each replica mutation is separate. Replica-wide writes and counters are not transactions, and a failure can leave only some copies updated. Schema maintenance, imports, and deletion algorithms use direct filesystem operations without these entry mutexes.

## Schema Operations

`Wrapper::getSchemaDriver(): SchemaOperations` returns a `SchemaDriver` using the wrapper's schema snapshot:

```php
$schemas = $db->getSchemaDriver();
if (!$schemas->exists()) {
    $schemas->create();
}
$filenames = $schemas->getAll(); // for example ["roles_users.json"]
```

| Method | Inputs | Output and behavior |
| --- | --- | --- |
| `create(): bool` | None | Attempts creation of unavailable folders with mode `0777`, subject to umask; returns true when every schema needed a creation attempt |
| `exists(): bool` | None | Whether every configured schema exists and is writable |
| `getCapacity(): int` | None | Entry count in one randomly selected replica |
| `getAll(): array` | None | Alphabetically sorted `.json` filenames from one random replica |
| `getByTag(string $tag): array` | Valid complete tag segment | Sorted matching filenames from one random replica |
| `deleteAll(): int` | None | Scans one replica and deletes those filenames across replicas; returns logical entry count |
| `drop(): bool` | None | Drops every available writable schema; returns false if any schema was already unavailable |

The driver's creation and drop booleans describe which schemas needed an operation; they do not aggregate individual filesystem results. Check `exists()` afterwards when availability must be confirmed.

For one folder, use `new Lucinda\DB\Schema($path)`. It also offers `populate(string $sourceSchema): void`, which copies missing source entries and preserves modification times. Existing destination filenames are retained. `Schema::drop()` returns whether the directory still exists, so successful removal returns **false**; `SchemaDriver::drop()` has the aggregate behavior described above.

Entry scans recognize `.json` filenames. Dropping a folder also removes other immediate regular files, but does not recursively remove child directories.

## Maintenance

Maintenance receives a filename because registration changes must be written back to XML:

```php
$maintenance = new Lucinda\DB\DatabaseMaintenance(__DIR__ . '/configuration.xml');
$statuses = $maintenance->checkHealth(0.1);
// array<string, Lucinda\DB\SchemaStatus>, keyed by configured schema path
```

| Method | Inputs | Output and behavior |
| --- | --- | --- |
| `checkHealth(float $maximumWriteDuration): array` | Acceptable write duration in seconds, including fractions | Schema-path-to-`SchemaStatus` map; writes, reads, and removes a probe |
| `plugIn(string $schema): void` | Existing writable directory not already registered | Imports missing files from the first schema, registers the path, then imports again |
| `plugOut(string $schema): void` | Existing writable registered directory | Unregisters the schema and clears its entries; retains the folder |
| `addSchema(string $schema): void` | Path to append | Low-level XML registration only; performs no import, directory validation, or duplicate check |
| `removeSchema(string $schema): void` | Exact registered path to remove | Low-level XML removal only; leaves entry files intact |
| `deleteByTag(string $tag): int` | Valid tag matched as a complete segment | Deletes matching filenames across replicas and returns logical entry count |
| `deleteUntil(int $secondsBeforeNow): int` | Entry age in seconds | Deletes files with modification time strictly before `time() - $secondsBeforeNow`; returns logical entry count |
| `deleteByCapacity(int $minCapacity, int $maxCapacity): int` | `0 <= minCapacity < maxCapacity` | Whenever its candidate heap reaches the maximum, evicts oldest entries down to the minimum; returns logical entries evicted |

```php
// The destination folder must already exist and be writable.
$maintenance->plugIn('/var/app/db/schema3');
$maintenance->plugOut('/var/app/db/schema3'); // same instance sees updated membership
```

Membership edits refresh the maintenance instance's configuration. Removing the final configured schema throws `ConfigurationException` before changing XML or entries. Use `plugIn` and `plugOut` for the complete workflows; `addSchema` and `removeSchema` expose only their configuration steps.

Health status values are:

| Case | Integer value | Meaning |
| --- | --- | --- |
| `ONLINE` | 1 | Probe succeeds within the write-duration threshold |
| `OFFLINE` | 2 | Directory is missing or unwritable |
| `UNRESPONSIVE` | 3 | Probe file is absent or its decoded contents differ |
| `OVERLOADED` | 4 | Probe succeeds but writing exceeds the threshold |

Probe I/O exceptions propagate rather than being converted into statuses. Maintenance deletion policies inspect one random replica and remove selected filenames across all configured replicas. Counts describe logical entries, not the number of physical files unlinked. Capacity eviction has no stable order for equal modification times.

Membership edits are not transactional or protected by a configuration-file lock. Existing wrappers and drivers retain their schema snapshots; reload XML and recreate them to use updated membership.

## Console Client

Run maintenance methods without an environment argument:

```console
php client.php configuration.xml deleteUntil 86400
php client.php configuration.xml plugIn /var/app/db/schema3
php client.php configuration.xml checkHealth 0.1
php client.php configuration.xml deleteByCapacity 100 200
```

Arguments are the XML filename, a public maintenance method, then its scalar parameters in declaration order. Integer arguments use unsigned decimal digits; float arguments use decimal notation such as `0.1`. Quote filesystem paths containing spaces. Private helpers and the constructor cannot be invoked.

Successful output goes to stdout with exit status zero. The body contains the method's return value, including `null` for void methods and integer values for health enum cases:

```json
{"status":"ok","body":null}
```

Errors go to stderr with exit status one:

```json
{"status":"error","body":"Unrecognized method: example"}
```

## Exceptions

| Exception | Meaning |
| --- | --- |
| `ConfigurationException` | Missing or invalid configuration, invalid membership operation, or attempted removal of the last schema |
| `KeyException` | Empty tags or unsupported characters in a key/filter tag |
| `KeyNotFoundException` | Required entry is absent within the operation's storage scope |
| `LockException` | Entry mutex could not be opened or immediately acquired |
| `JsonException` | Value cannot be encoded or stored JSON cannot be decoded |
| `RuntimeException` | Atomic replacement cannot create, write, or rename its temporary file |

Filesystem and XML functions may also emit PHP warnings. Health checks and maintenance operations do not convert all I/O failures into domain exceptions.

## Constraints

- Supply JSON-encodable values and existing, appropriately writable schema paths.
- Direct `ValueDriver`, `SchemaDriver`, and capacity-policy construction requires a nonempty schema list indexed consecutively from zero.
- Random-replica schema queries and deletion policies assume compatible replica contents. An entry found only in an unscanned replica can be omitted.
- There is no versioning, quorum resolution, or transaction spanning all replicas.
- Schema population retains destination files already present, even if their contents differ.
- Directory deletion is not recursive. Entry scanning filters filename suffixes rather than verifying that every matching path is a regular file.

## Testing

```console
composer install
php test.php
```

Run one test class by its fully qualified name:

```console
php test.php 'Test\Lucinda\DB\DatabaseMaintenanceTest'
```

Tests use `lucinda/unit-testing` and return `Lucinda\UnitTest\Result` objects through validator assertions. Configuration coverage exercises the environment-independent XML format. Maintenance and value-driver tests use disposable directories; maintenance tests keep XML edits in temporary files instead of modifying `tests/tests.xml`.

Regression coverage includes schema snapshot refresh, duplicate registration rejection through `plugIn`, removal of the final schema without data/configuration changes, replica repair, and console argument binding. Run tests from the repository root because the suite configuration and some older fixtures use relative paths.
