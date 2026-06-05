# Lucinda DB

Lucinda DB is a pure PHP, file-backed key-value store. Each entry is stored as one JSON file inside one or more schema folders. Keys are generated from tags, so the same unordered tag set always maps to the same file name.

The package is useful when you want a small serverless cache/database that can be copied, backed up, or distributed by moving folders on disk.

## Requirements

- PHP 8.1+
- SPL extension
- SimpleXML extension
- XML/DOM extension
- Composer autoloading

Development tests use `lucinda/unit-testing`.

## Installation

Install with Composer:

```console
composer require lucinda/db
```

Then create at least one writable schema folder and reference it in XML configuration.

## Storage Model

An entry is made of:

- **Data**: any JSON-encodable PHP value.
- **Tags**: lowercase labels used to derive a key.
- **Key**: tags sorted alphabetically and joined with `_`.
- **Value file**: `{key}.json` in a schema folder.
- **Schema**: a writable folder that stores value files.

Tag rules are enforced by `Lucinda\DB\Key`:

- at least one tag is required
- only lowercase `a-z`, digits, and `-` are allowed
- tags are sorted before key generation

```php
use Lucinda\DB\Key;

$key = new Key(["users", "roles"]);
echo $key->getValue(); // roles_users
```

## Configuration

`Lucinda\DB\Configuration` reads schema folders from XML. The selected environment must exist and must contain at least one schema.

```xml
<?xml version="1.0" encoding="utf-8"?>
<xml>
  <lucinda_db>
    <local>
      <schemas>
        <schema>/var/app/db/schema1</schema>
        <schema>/var/app/db/schema2</schema>
      </schemas>
    </local>
  </lucinda_db>
</xml>
```

```php
use Lucinda\DB\Configuration;

$configuration = new Configuration(__DIR__."/configuration.xml", "local");
$schemas = $configuration->getSchemas();
```

`Configuration` can also update the XML schema list:

- `addSchema(string $schema): void`
- `removeSchema(string $schema): void`

## Entry Operations

Most applications should use `Lucinda\DB\Wrapper`, which creates drivers from XML configuration.

```php
use Lucinda\DB\Wrapper;

$db = new Wrapper(__DIR__."/configuration.xml", "local");

$entry = $db->getEntryDriver(["users", "roles"]);
$entry->set(["Jane Doe" => ["Admin"]]);

if ($entry->exists()) {
    $data = $entry->get();
}
```

`getEntryDriver(array $tags)` returns `Lucinda\DB\ValueOperations`. The current implementation is `Lucinda\DB\ValueDriver`, which writes to every configured schema and reads from available replicas.

Supported entry methods:

| Method | Description |
| --- | --- |
| `set(mixed $value): void` | Stores a JSON-encodable value. |
| `get(): mixed` | Reads an existing value. Throws `KeyNotFoundException` when no replica has it. |
| `exists(): bool` | Checks all configured replicas for the key. |
| `increment(int $step = 1): int` | Increments an existing integer value. |
| `decrement(int $step = 1): int` | Decrements an existing integer value. |
| `delete(): void` | Deletes the entry from every replica where it exists. |

Direct single-schema access is available through `Lucinda\DB\Value`:

```php
use Lucinda\DB\Key;
use Lucinda\DB\Value;

$key = new Key(["users", "roles"]);
$entry = new Value("/var/app/db/schema1", $key->getValue());
$entry->set(["Jane Doe" => ["Admin"]]);
```

## Replication And Locking

`ValueDriver` treats the configured schemas as replicas:

- `set` writes the same value to every schema.
- `get` tries schemas in randomized order until one valid JSON value is found.
- after a successful read, missing or corrupted replicas are repaired with the good value when possible.
- `exists` returns true if any replica has the key.
- `increment` and `decrement` update the first configured schema, then copy the resulting value to the other schemas.

`Lucinda\DB\File` protects writes, updates, and deletes with a non-blocking file mutex. If the mutex cannot be acquired, `Lucinda\DB\LockException` is thrown. Writes use a temporary file followed by `rename`, so readers should not observe partial JSON writes.

## Schema Operations

`Wrapper::getSchemaDriver()` returns `Lucinda\DB\SchemaOperations`. The current implementation is `Lucinda\DB\SchemaDriver`, which coordinates operations across configured schemas.

```php
$db = new Lucinda\DB\Wrapper(__DIR__."/configuration.xml", "local");
$schemas = $db->getSchemaDriver();

if (!$schemas->exists()) {
    $schemas->create();
}

$allKeys = $schemas->getAll();
```

Supported schema methods:

| Method | Description |
| --- | --- |
| `create(): bool` | Creates missing schema folders. |
| `drop(): bool` | Deletes entries and removes schema folders. |
| `exists(): bool` | Checks that every schema exists and is writable. |
| `getCapacity(): int` | Returns entry count from one random schema. |
| `getAll(): array` | Returns sorted `.json` entry names from one random schema. |
| `getByTag(string $tag): array` | Returns sorted entry names whose key contains a tag. |
| `deleteAll(): int` | Deletes all entries across replicas. |

Direct single-schema access is available through `Lucinda\DB\Schema`.

## Maintenance

`Lucinda\DB\DatabaseMaintenance` performs operational tasks using the configured schema list.

```php
use Lucinda\DB\DatabaseMaintenance;

$maintenance = new DatabaseMaintenance(__DIR__."/configuration.xml", "local");
$statuses = $maintenance->checkHealth(0.1);
```

Supported maintenance methods:

| Method | Description |
| --- | --- |
| `checkHealth(float $maximumWriteDuration): array` | Writes and reads a probe file in each schema and returns `SchemaStatus` by schema path. |
| `plugIn(string $schema): void` | Copies entries from the first configured schema, adds the new schema to XML, then copies again to catch late writes. |
| `plugOut(string $schema): void` | Removes a schema from XML and clears its entries. |
| `deleteByTag(string $tag): int` | Deletes entries whose key contains the tag across replicas. |
| `deleteUntil(int $secondsBeforeNow): int` | Deletes entries older than the given age across replicas. |
| `deleteByCapacity(int $minCapacity, int $maxCapacity): int` | When capacity reaches `maxCapacity`, removes older entries until `minCapacity` remains. |

Health statuses are defined by `Lucinda\DB\SchemaStatus`:

- `ONLINE`
- `OFFLINE`
- `UNRESPONSIVE`
- `OVERLOADED`

## Console Client

`client.php` can invoke `DatabaseMaintenance` methods from the command line.

```console
php client.php configuration.xml local deleteUntil 86400
php client.php configuration.xml local plugIn /var/app/db/schema3
php client.php configuration.xml local checkHealth 0.1
```

Output is JSON:

```json
{"status":"ok","body":null}
```

Errors are written to stderr:

```json
{"status":"error","body":"Unrecognized method: example"}
```

## Exceptions

The package uses small domain exceptions:

- `ConfigurationException`: missing or invalid XML configuration.
- `KeyException`: invalid tag list or tag format.
- `KeyNotFoundException`: requested entry does not exist.
- `LockException`: entry mutex could not be acquired.

JSON encoding and decoding errors are thrown as `JsonException`.

## Constraints

- Values must be JSON-encodable.
- Only `.json` files are treated as database entries during scans and deletes.
- Tag-based lookup matches whole key segments, for example `users_roles.json` matches `users`.
- Schema folders must already be writable for normal entry operations.
- `getCapacity`, `getAll`, and `getByTag` on `SchemaDriver` inspect one random replica, so they assume replicas are mostly consistent.
- This is a file-backed store, so it is best suited for modest write concurrency and persistent cache-like data.

## Testing

Run the test suite with:

```console
php test.php
```

Tests are written for `lucinda/unit-testing`. Each public test method returns a `Lucinda\UnitTest\Result` or an array of results, usually through validators in `Lucinda\UnitTest\Validator`.
