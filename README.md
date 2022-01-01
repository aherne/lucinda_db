# Lucinda DB: Pure PHP Tag-Based Key-Value Store

Table of contents:

- [About](#about)
   - [DATA](#data)
   - [KEY](#keys)
   - [VALUE](#values)
   - [SCHEMA](#schema)
- [Installation](#installation)
- [Configuration](#configuration)
- [Querying](#querying)
   - [Querying Entries](#querying-entries)
   - [Querying Schemas](#querying-schemas)
- [Maintenance](#maintenance)
   - [By Cron Job](#by-cron-job)
   - [By Console Command](#by-console-command)
- [Advanced Guide](#advanced-guide)
   - [Specializing Keys](#specializing-keys)
   - [Handling Race Conditions](#handling-race-conditions)
   - [Checking Schemas Health](#checking-schemas-health)
   - [Plugging Schema In](#pluging-schema-in)
   - [Plugging Schema Out](#pluging-schema-out)
   - [Deleting Entries by Tag](#deleting-entries-by-tag)
   - [Deleting Entries by Time](#deleting-entries-by-time)
   - [Deleting Entries by Capacity](#deleting-entries-by-capacity)
   - [Avoiding API Disadvantages](#avoiding-api-disadvantages)
- [Usage Examples](#examples)

## About

Lucinda DB is serverless KEY-VALUE store originally designed to help developers cache results of resource-intensive SQL queries based to criteria (aka TAG) query depends on. It is different from other KV stores by having KEYs self-generate based on TAGs query result depended on and VALUEs saved as individual JSON files (instead of RAM) named by KEY in one/more SCHEMAs. This brings following advantages:

- **ability of working without a server**: operating system on host machine, already optimized to manipulate files, becomes the server
- **ability of being platform agnostic**: a database specification that can be implemented in any programming language on any operating system
- **key standardization**: value of a KEY is generated according to a predictable rule based on value of TAGs it depends on
- **no entry duplication**: combination of TAGs will always be unique
- **easy maintenance**: entries can be queried by TAG, something impossible in standard KV stores that purely rely on RAM hash tables indexed by cumulative key
- **portability**: to transfer/backup database, it is as easy as copying schema folder(s)
- **scalability**: ability of database to be distributed on multiple disks in real time

API relies on an interplay of following concepts:

- **[DATA](#data)**: this is value of data to cache (eg: result of your SQL query or query combinations)
- **[TAG](#tag)**: criteria based on whome DATA was generated (eg: "users", "roles")
- **[KEY](#key)**: the key in KV store, whose name was generated automatically based on TAGs it depends on (eg: "roles_users")
- **[VALUE](#value)**: the value in KV store present as json-ed DATA saved on disk as a separate file in SCHEMA folder named by KEY
- **[SCHEMA](#schema)**: folders/disks in which KV entries are stored

### Data

This is result to cache as VALUE in KV store, convertible to JSON.

Example query:

```sql
SELECT t1.name AS user, t3.name AS role
FROM users AS t1
INNER JOIN users__roles AS t2 ON t1.user_id = t2.user_id
INNER JOIN roles AS t3 ON t2.role_id = t3.id
WHERE t1.active = 1
```

Processed into following PHP array structure:

```php
$data = ["John Doe"=>["Administrator"], "Jane Doe"=>["Assistant Manager", "Team Leader"]];
```

### Tag

A tag corresponds to name of criteria based on whom [DATA](#data) was generated (eg: "users" and "roles" for example above). A tag's value must obey following requirements:

- must be lowercase
- can only contain a-z0-9 characters
- **-** sign is allowed as separator of multi-word names

### Key

A key is unique identifier of [DATA](#data) in KV store named by combination of [TAG](#tag)s data depended on (eg: "roles_users" above) . To make things easier for maintenance, each finite combination of [TAG](#tag)s results into a single KEY, regardless of how they were ordered by caller! The rules based on whom key name is calculated are:

- checks if [TAG](#tag)s obey above specifications
- sorts [TAG](#tag)s alphabetically
- joins all [TAG](#tag)s using **_** sign

Key creation is encapsulated by **[Lucinda\DB\Key](https://github.com/aherne/lucinda_db/blob/master/src/Key.php)** class. Usage example:

```php
$object = new Lucinda\DB\Key(["users", "roles"]);
$key = $object->getValue(); // key will be "roles_users"
```

### Value

A value is a JSON-ed representation of [DATA](#data) saved as **.json** file named by [KEY](#key) inside [SCHEMA](#schema) folder according to following rules:

- [DATA](#data) must be json encodable

Value operations are encapsulated by **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** class. Usage example:

```php
$key = new Lucinda\DB\Key(["users", "roles"]); // initializes KEY
$value = new Lucinda\DB\Value("/usr/local/share/db", $key->getValue()); // instances VALUE by KEY and SCHEMA
$value->set($data); // saves DATA by KEY, creating a KEY.json file within SCHEMA
```

#### Load Balancing

For performance, consistency or scalability reasons, users can opt for VALUEs to be load balanced across multiple [SCHEMA](#schema)s. This is done by using **[Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php)** instead, which wraps **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** in order to insure that:

- all writes (set/delete) are evenly distributed among replicas
- all reads are done from random replica
- all race condition operations (increment/decrement) will be done in first replica, then distributed to all others

Usage example:

```php
$key = new Lucinda\DB\ValueDriver(["schema1", "schema2"], ["users", "roles"]); //instances VALUE by KEY and SCHEMAs
$value->set($data); // saves DATA by KEY, creating a KEY.json file within all SCHEMAs
```

### Schema

A schema is simply the folder where [VALUE](#value)s are saved. A schema can be a single folder or an array of replicas that can be located on different disks from same server or even different servers (eg: via symlinks). Class **[Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php)** encapsulates operations one can perform on a single schema. Usage example:

```php
$key = new Lucinda\DB\Schema("schema1"); // initializes SCHEMA
$value->deleteAll(); // deletes all VALUEs in SCHEMA
```

#### Load Balancing

For performance, consistency or scalability reasons, users can opt for SCHEMAs to be load balanced and evenly distributed. This is done via **[Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php)** class that insures all schema operations are automatically reflected in replicas.  Usage example:

```php
$key = new Lucinda\DB\SchemaDriver(["schema1", "schema2"]); // initializes SCHEMAs
$value->deleteAll(); // deletes all VALUEs in all SCHEMAs
```

## Installation

First choose a folder, then write this command there using console:

```console
composer require lucinda/db
```

Then create *at least one* [SCHEMA](#schema) and follow [configuration](#configuration) guide in order to set it/them in XML required to configure API. Once latter is done, you will be able to query database using **[Lucinda\DB\Wrapper](https://github.com/aherne/lucinda_db/blob/master/src/Wrapper.php)** object. Usage example:

```PHP
require 'vendor/autoload.php';

$object = new Lucinda\DB\Wrapper(XML_CONFIGURATION_PATH, DEVELOPMENT_ENVIRONMENT);
$value = $wrapper->getEntryDriver(["users", "roles"]);
$value->set(["John Doe"=>["Administrator"], "Jane Doe"=>["Assistant Manager", "Team Leader"]]);
```

API uses composer autoload, requires PHP 8.1+ and has no external dependencies except SimpleXML, DOM and SPL extensions. All code inside is 100% unit tested and developed on simplicity and elegance principles!

## Configuration

To configure this API you must have a XML with a **lucinda_db** tag inside:

```xml
<lucinda_db>
	<{ENVIRONMENT}>
		<schemas>
      <schema>{SCHEMA}</schema>
      ...
    </schemas>
	</{ENVIRONMENT}>
	...
</lucinda_db>
```

Where:

- **lucinda_db**: (mandatory) holds database configuration
    - {ENVIRONMENT}: name of development environment (to be replaced with "local", "dev", "live", etc)
        - **schemas**: (mandatory) stores list of schemas to be load balanced, each as **schema** tag
            - **schema**: (mandatory) holds path to a single schema that takes part of load balancing scheme

Example:

```xml
<lucinda_db>
    <local>
        <schemas>
          <schema>C:\db</schema>
        </schemas>
    </local>
    <live>
        <schemas>
          <schema>/usr/local/share/db</schema>
          <schema>/mnt/remote/db</schema>
        </schemas>
    </live>
</lucinda_db>
```

## Querying

Now that XML is configured, you can query entries or schemas via **[Lucinda\DB\Wrapper](https://github.com/aherne/lucinda_db/blob/master/src/Wrapper.php)** class and its methods available:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string $xmlFilePath, string $developmentEnvironment | void | Sets location of [configuration](#configuration) file along with development environment for later querying |
| getEntryDriver | string[] $tags | [Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php) | Gets object to query load-balanced entry list of tags key depends on |
| getSchemaDriver | void | [Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php) | Gets object to query distributed schemas |


### Querying Entries

Usage example employing **[Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php)**:

```php
$object = new Lucinda\DB\Wrapper("/var/www/html/myapp/configuration.xml", "local");
$driver = $wrapper->getEntryDriver(["users", "roles"]);
$driver->set(["John Doe"=>["Administrator"], "Jane Doe"=>["Assistant Manager", "Team Leader"]]);
```

Using **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** directly is useful only if your app requires no load balancing, lies in one development environment only and needs only primitive maintenance. Usage example:

```php
$key = new Lucinda\DB\Key(["users", "roles"]);
$value = new Lucinda\DB\Value("/usr/local/share/db", $key->getValue());
$value->set(["John Doe"=>["Administrator"], "Jane Doe"=>["Assistant Manager", "Team Leader"]]);
```

Both **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** and its **[Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php)** wrapper abide to a single contract defining blueprints of [VALUE](#value) operations through interface **[Lucinda\DB\ValueOperations](https://github.com/aherne/lucinda_db/blob/master/src/ValueOperations.php)**, which comes with following prototype methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| set | mixed $value | void | Sets entry value |
| get | void | mixed | Gets entry value |
| exists | void | bool | Checks if entry exists |
| increment | int $step = 1 | int | Increments **existing** entry value using [locking](#locking). Throws [Lucinda\DB\LockException](https://github.com/aherne/lucinda_db/blob/master/src/LockException.php) on race condition!<br/>See: [Handling Race Conditions](#handling-race-conditions) |
| decrement | int $step = 1 | int | Decrements **existing** entry value using [locking](#locking). Throws [Lucinda\DB\LockException](https://github.com/aherne/lucinda_db/blob/master/src/LockException.php) on race condition!<br/>See: [Handling Race Conditions](#handling-race-conditions) |
| delete | void | void | Deletes entry |

As one can see above, in case developers opt using **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** directly, class **[Lucinda\DB\Key](https://github.com/aherne/lucinda_db/blob/master/src/Key.php)** needs to be instanced manually. It encapsulates creation of keys based on tags and defines following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string[] tags | void | Compiles key based on [TAG](#tag)s. Throws [Lucinda\DB\KeyException](https://github.com/aherne/lucinda_db/blob/master/src/KeyException.php) if [TAG](#tag)s break naming rules |
| getValue |  | string | Gets entry key compiled above. |

### Querying Schemas

Usage example employing **[Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php)**:

```php
$object = new Lucinda\DB\Wrapper("/var/www/html/myapp/configuration.xml", "local");
$driver = $wrapper->getSchemaDriver();
$driver->deleteAll();
```

Using **[Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php)** directly is useful only if your app requires no load balancing, lies in one development environment only and needs only primitive maintenance. Usage example:


```php
$object = new Lucinda\DB\Schema("/usr/local/share/db");
$object->deleteAll();
```
Both **[Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php)** and **[Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php)** abide to a single contract defining blueprints of [SCHEMA](#schema) operations through interface **[Lucinda\DB\SchemaOperations](https://github.com/aherne/lucinda_db/blob/master/src/SchemaOperations.php)**, which comes with following prototype methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| create | void | bool | Creates schema(s) |
| exists | void | bool | Checks if schema(s) exist |
| getAll | void | string[] | Gets all keys in schema(s) |
| getByTag | string $tag | string[] | Gets all keys in schema(s) matching tag |
| getCapacity | void | int | Gets number of entries in schema(s) |
| deleteAll | void | int | Deletes all entries in schema(s) |
| drop | void | bool | Drops all schema(s), deleting all entries in the process |

## Maintenance

Modern operating systems allow up to 4,294,967,295 files in one folder but you shouldn't go anywhere near that value! Just like MySQL running out of disk space, LucindaDB may additionally run out of [VALUE](#value)s in [SCHEMA](#schema), something that usually happens only when [specialization](#specialization) is used at massive scale.

To fix such cases class **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** class was created, whose purpose is to do automated maintenance through following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string $xmlFilePath, string $developmentEnvironment | void | Sets location of [configuration](#configuration) file along with development environment for later querying |
| checkHealth | float $maximumWriteDuration | [Lucinda\DB\SchemaStatus](https://github.com/aherne/lucinda_db/blob/master/src/SchemaStatus.php)[string] | Performs health checks of all load balanced schemas and returns results as status by schema.<br/>See: [Checking Schemas Health](#checking-schemas-health) |
| plugIn | string $schema | void | Plugs in schema to load-balanced DB without down times<br/>See: [Plugging Schema In](#pluging-schema-in) |
| plugOut | string $schema | void | Plugs out schema from load-balanced DB without down times<br/>See: [Plugging Schema Out](#pluging-schema-out) |
| deleteByTag | string $tag | int | Deletes all DB entries whose key matches [TAG](#tag)<br/>See: [Deleting Entries by Tag](#deleting-entries-by-tag) |
| deleteUntil | int $secondsBeforeNow | int | Deletes all DB entries whose last modified time is more than #seconds old<br/>See: [Deleting Entries by Time](#deleting-entries-by-time) |
| deleteByCapacity | int $minCapacity, int $maxCapacity | int | Deletes all DB entries by keeping schema at fixed max capacity range based on entry last modified time.<br/>See: [Deleting Entries by Capacity](#deleting-entries-by-capacity) |

### By Cron Job

This class should be used via a cron job whose periodicity depends on the chance of your project to get filled! Example:

```php
require 'vendor/autoload.php';

$maintenance = new Lucinda\DB\DatabaseMaintenance(XML_CONFIGURATION_PATH, DEVELOPMENT_ENVIRONMENT);
// checks schema health and plugs out unresponsive ones
$statuses  = $maintenance->checkHealth();
foreach ($statuses as $schema=>$status) {
  if (in_array($status, [DatabaseMaintenance::STATUS_OFFLINE, DatabaseMaintenance::STATUS_UNRESPONSIVE])) {
    $maintenance->plugOut($schema);
  }
}
// performs delete of all entries older than one day
$maintenance->deleteUntil(time()-(24*3600));
```

### By Console Command

If maintenance involves just one operation it can be done without programming by calling **client.php** file bundled in API root. Console syntax:

```console
php PATH_TO_CLIENT_PHP OPERATION ARGUMENTS
``` 

Where:

- PATH_TO_CLIENT_PHP: absolute location of **client.php** file (example: */var/www/html/mysite/vendor/lucinda/db/client.php*)
- OPERATION: name of [Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php) method (example: *deleteUntil*)
- ARGUMENTS: arguments to call method above, separated by space (example: *3600*)

The greatest advantage of this solution is that it allows non-programmers (devops) to perform maintenance from command line. Example:

```console
php /var/www/html/mysite/vendor/lucinda/db/client.php plugIn /my/new/schema

```

## Advanced Guide

### Specializing Keys

Sometimes, different [VALUE](#value)s need to be produced for same [TAG](#tag) combination. This requires us to have different keys, while abiding to principles described in [KEY](#key) section at the same time. Solution is to add an extra *specializer* tag (eg: MD5 checksum of query) when creating key:

```php
$object = new Lucinda\DB\Key(["users", "roles", md5($query)]);
$key = $object->getValue(); // key will be "54ed347f362bb056e4d6db0477bf19c9_roles_users"
```

As a rule, specialization should be avoided as much as possible, since it enlarges database and has a duplication potential (for example a simple extra space in query above would generate another key)!

### Handling Race Conditions

A complicated problem in all databases is managing *race conditions*. What happens when a increment or decrement operation is ran in parallel? Let's imagine [DATA](#data) was 8 and increment is attempted at same moment Z by users X and Y:

```php
# user X increments value at moment Z
$value->increment(1);
# user Y increments value at same moment Z
$value->increment(1);
```

Will end result be 10, as expected? The answer is no, because both processes got 8 to increment at same time! This situation is called a "race condition" and the only solution is to stack writes on that entry instead of letting them run in parallel.

For increment/decrement [VALUE](#value) operations respective file is locked for writes and unlocked only when value update completes. If a concurrent process tries to write to a still locked file, a **[Lucinda\DB\LockException](https://github.com/aherne/lucinda_db/blob/master/src/LockException.php)** is thrown. Instead of letting exception bubble, developers can catch it and retry after delay:

```php
try {
  $value->increment(1);
} catch(Lucinda\DB\LockException $e) {
  usleep(100);
  $value->increment(1);
}
```

### Checking Schemas Health

Method *checkHealth* @ class **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** is to be used in checking the state of each replica [SCHEMA](#schema) and produce a [Lucinda\DB\SchemaStatus](https://github.com/aherne/lucinda_db/blob/master/src/SchemaStatus.php). The algorithm by which statuses are produced is:

- if folder (schema) doesn't exist, status is OFFLINE
- otherwise, if files can't be written to schema, status is UNRESPONSIVE
- otherwise, if file writes take longer than maximumWriteDuration, status is OVERLOADED
- otherwise, status is ONLINE

The end result of all checks will be returned as an array where key is schema and value is status found.

### Plugging Schema In

Method *plugIn* @ class **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** is to be used for plugging a [SCHEMA](#schema) to replicas without database down times. The algorithm used is:

- if [SCHEMA](#schema) doesn't exist or it is already plugged, a [Lucinda\DB\ConfigurationException](https://github.com/aherne/lucinda_db/blob/master/src/ConfigurationException.php) is thrown
- copies all files from first replica to [SCHEMA](#schema)
- plugs in [SCHEMA](#schema) into XML
- copies all remaining files from first replica to [SCHEMA](#schema) that may have been inserted to former as initial copy process was running

### Plugging Schema Out

Method *plugOut* @ class **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** is to be used for plugging out a [SCHEMA](#schema) to replicas without database down times. The algorithm used is:

- if [SCHEMA](#schema) doesn't exist or it is not plugged, a [Lucinda\DB\ConfigurationException](https://github.com/aherne/lucinda_db/blob/master/src/ConfigurationException.php) is thrown
- [SCHEMA](#schema) is removed from XML, which insures no further writes will occur
- all files are deleted in [SCHEMA](#schema)

### Deleting Entries by Tag

Method *deleteByTag* @ class **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** is to be used for removing all [VALUE](#value)s whose [KEY](#key) matches [TAG](#tag) from all replicas. The algorithm used is:

- iterates entries in random replica [SCHEMA](#schema)
- if entry [KEY](#key) matches [TAG](#tag)
   - for each [SCHEMA](#schema) replica
       - deletes entry ([VALUE](#value)) by [KEY](#key) above

### Deleting Entries by Time

Method *deleteUntil* @ class **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** is to be used for removing all [VALUE](#value)s whose last modified time is more than $secondsBeforeNow old. The algorithm used is:


- iterates entries in random [SCHEMA](#schema) replica
- if entry [VALUE](#value) last modified time is more than $secondsBeforeNow old
   - remembers entry [KEY](#key)
   - for each [SCHEMA](#schema) replica
       - deletes entry ([VALUE](#value)) by [KEY](#key) above

### Deleting Entries by Capacity

Method *deleteByCapacity* @ class **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** is to be used to insure number of entries in database doesn't exceed $maxCapacity and, if it does, shrink it to $minCapacity by having older entries removed. The algorithm used is:

- iterates entries in random [SCHEMA](#schema) replica
- records entry [KEY](#key) to a fixed capacity **SplMaxHeap** sorted by [VALUE](#value)'s last modified time
- if heap reached $maxCapacity, pops head and deletes entry until $minCapacity is reached

### Avoiding API Disadvantages

This API, being disk based, comes with its own disadvantages compared to standard RAM-based KV stores:

- *slightly reduced speed*: hard drives will always be slower than RAM, but considering how fast SSDs are this won't be a problem unless your app has very high thoroughput
- *no expiration for entries*: all entries inside, being separate files, persist forever unless specifically deleted. This can be solved by using [Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)!
- *not suitable for temporary/volatile data*: if your app expects database entries to be volatile (changing randomly) and [specializing keys](#specializing-keys) is required at massive scale, standard RAM-based KV stores are highly recommended
- *requires daemonized maintenance*: in case volatility is expected, a program must periodically remove old files in order prevent disk(s) getting full. . This can be solved by using [Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)!

It is always up for developers to decide which KV store model fits their application the best! More often than not you will need to employ multiple stores (eg: LucindaDB and Redis) to cover all usage cases (one for persistent query-able data, the other for volatile data).

## Usage Examples

To see usage examples, these unit tests should be enough:

- [ValueDriverTest](https://github.com/aherne/lucinda_db/blob/master/tests/ValueDriverTest.php): testing **[Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php)** operations
- [SchemaDriverTest](https://github.com/aherne/lucinda_db/blob/master/tests/SchemaDriverTest.php): testing **[Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php)** operations
- [DatabaseMaintenanceTest](https://github.com/aherne/lucinda_db/blob/master/tests/DatabaseMaintenanceTest.php): testing **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** operations
