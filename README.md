# Lucinda DB: Pure PHP Tag-Based Key-Value Store

Table of contents:

- [About](#about)
   - [DATA](#data)
   - [KEY](#keys)
   - [VALUE](#values)
   - [SCHEMA](#schema)
- [Installation](#installation)
- [Configuration](#configuration)
- [Documentation](#documentation)
   - [Querying](#querying)
   - [Locking](#locking)
   - [Load Balancing](#load-balancing)
   - [Replication](#replication)
   - [Maintenance](#maintenance)
- [Unit Tests](#unit-tests)
- [Usage Examples](#examples)
- [Reference Guide](#reference-guide)

## About

Lucinda DB is serverless KEY-VALUE store originally designed to help developers cache results of resource-intensive SQL queries based to criteria (aka TAG) query depends on. It is different from other KV stores by having KEYs self-generate based on TAGs query result depended on and VALUEs saved as individual JSON files (instead of RAM) named by KEY in one/more SCHEMAs. This brings following advantages:

- **ability of working without a server**: operating system on host machine, already optimized to manipulate files, becomes the server
- **ability of being platform agnostic**: a database specification that can be implemented in any programming language on any operating system
- **key standardization**: value of a KEY is generated according to a predictable rule based on value of TAGs it depends on
- **no entry duplication**: combination of TAGs will always be unique
- **easy maintenance**: entries can be queried by TAG, something impossible in standard KV stores that purely rely on RAM hash tables indexed by cumulative key
- **portability**: to transfer/backup database, it is as easy as copying schema folder(s)
- **scalability**: ability of database to be distributed on multiple disks in real time


As stated in introduction text, Lucinda DB relies on an interplay of following concepts:

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

#### Specialization

Sometimes, different VALUEs need to be produced for same tag combination. This requires us to have different keys, while abiding to principles described in [KEY](#key) section, thus add an extra *specializer* tag (eg: MD5 checksum of that query) when creating key:

```php
$object = new Lucinda\DB\Key(["users", "roles", md5($query)]);
$key = $object->getValue(); // key will be "54ed347f362bb056e4d6db0477bf19c9_roles_users"
```

As a rule, specialization should be avoided as much as possible, since it enlarges database and has a duplication potential (for example a simple extra space in query above would generate another key)!

### Value

A value is a JSON-ed representation of [DATA](#data) saved as **.json** file named by [KEY](#key) inside [SCHEMA](#schema) folder according to following rules:

- [DATA](#data) must be json encodable

Value operations are encapsulated by **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** class. Usage example:

```php
$key = new Lucinda\DB\Key(["users", "roles"]); // initializes KEY
$value = new Lucinda\DB\Value("/usr/local/share/db", $key->getValue()); // instances VALUE
$value->set($data); // saves DATA by KEY, creating a KEY.json file within SCHEMA
```

For performance, consistency or scalability reasons, users can opt for VALUEs to be load balanced across multiple [SCHEMA](#schema)s. This is done by using **[Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php)** instead, which wraps **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** in order to insure that:

- all writes (set/delete) are evenly distributed among replicas
- all reads are done from random replica
- all race condition operations (increment/decrement) will be done in first replica, then distributed to all others

Usage example:

```php
$key = new Lucinda\DB\ValueDriver(["schema1", "schema2"], ["users", "roles"]); // initializes KEY
$value->set($data); // saves DATA by KEY, creating a KEY.json file within SCHEMA
```

Both **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** and **[Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php)** abide to a single contract defining blueprints of VALUE operations through interface **[Lucinda\DB\ValueOperations](https://github.com/aherne/lucinda_db/blob/master/src/ValueOperations.php)** defining following prototype methods:



## Installation

First choose a folder, then write this command there using console:

```console
composer require lucinda/mvc
```

Then create *at least one* folder where entries will be stored and follow [configuration](#configuration) guide in order to set it as schema in XML. Once latter is done, you will be able to query database using something like:

```PHP
require 'vendor/autoload.php';

$object = new Lucinda\DB\Wrapper(XML_CONFIGURATION_PATH, DEVELOPMENT_ENVIRONMENT);
$value = $wrapper->getEntryDriver(["users", "roles"]);
$value->set(["John Doe"=>["Administrator"], "Jane Doe"=>["Assistant Manager", "Team Leader"]]);
```

API uses composer autoload, requires PHP 7.1+ and has no external dependencies except SimpleXML and DOM extensions. All code inside is 100% unit tested!

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

Now that XML is configured, you can query entries or schemas via [Lucinda\DB\Wrapper](https://github.com/aherne/php-logging-api/blob/master/src/Wrapper.php). Object has following methods available:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string $xmlFilePath, string $developmentEnvironment | void | Sets location of [configuration](#configuration) file along with development environment for later querying |
| getEntryDriver | string[] $tags | [Lucinda\DB\ValueOperations](https://github.com/aherne/lucinda_db/blob/master/src/ValueOperations.php) | Gets [Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php) to query load-balanced entry list of tags key depends on |
| getSchemaDriver | void | [Lucinda\DB\SchemaOperations](https://github.com/aherne/lucinda_db/blob/master/src/SchemaOperations.php) | Gets [Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php) to query distributed schemas |

Usage example:

```php
$object = new Lucinda\DB\Wrapper("/var/www/html/myapp/configuration.xml", "local");
$value = $wrapper->getEntryDriver(["users", "roles"]);
$value->set(["John Doe"=>["Administrator"], "Jane Doe"=>["Assistant Manager", "Team Leader"]]);
```

Using [Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php) and [Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php) you can work with entries and schemas directly, without any [configuration](#configuration) file or [Lucinda\DB\Wrapper](https://github.com/aherne/php-logging-api/blob/master/src/Wrapper.php). This is useful only if your app requires no load balancing, lies in one development environment only and needs only basic maintenance!

Usage example:

```php
$key = new Lucinda\DB\Key(["users", "roles"]);
$value = new Lucinda\DB\Value("/usr/local/share/db", $key->getValue());
$value->set(["John Doe"=>["Administrator"], "Jane Doe"=>["Assistant Manager", "Team Leader"]]);
```

Regardless of solution chosen, list of operations one can perform on entries and schemas are codified by following interfaces:

- [Lucinda\DB\ValueOperations](https://github.com/aherne/lucinda_db/blob/master/src/ValueOperations.php): defines prototypes for operations to perform on a database entry.
- [Lucinda\DB\SchemaOperations](https://github.com/aherne/lucinda_db/blob/master/src/SchemaOperations.php): defines prototypes for operations to perform on a database schema.


## Documentation

As stated in introduction text, Lucinda DB relies on an interplay of following logical components:

- *DATA*: this is value of data to cache (eg: result of your SQL query or query combinations)
- *TAGs*: criteria based on whome DATA was generated (eg: "users", "roles")
- *KEYs*: the key in KV store, whose name was generated automatically based on TAGs it depends on (eg: "roles_users")
- *VALUEs*: the value in KV store present as json-ed DATA saved on disk as a separate file in SCHEMA folder named by KEY
- *SCHEMAs*: folders/disks in which KV entries are stored

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

### Tags

A tag corresponds to name of criteria based on whom DATA was generated (eg: "users" and "roles" for example above). A tag's value must obey following requirements:

- must be lowercase
- can only contain a-z0-9 characters
- **-** sign is allowed as separator of multi-word names

### Keys


A key is unique identifier of DATA in KV store named by combination of TAGs data depended on (eg: "roles_users" above) . To make things easier for maintenance, each finite combination of TAGs results into a single KEY, regardless of how they were ordered by caller! The rules based on whom KEY name is calculated are:

- checks if TAGs obey above specifications
- sorts TAGs alphabetically
- joins all TAGS using **_** sign

Key creation is encapsulated by **[Lucinda\DB\Key](https://github.com/aherne/lucinda_db/blob/master/src/Key.php)** class. Usage example:

```php
$object = new Lucinda\DB\Key(["users", "roles"]);
$key = $object->getValue(); // key will be "roles_users"
```

Sometimes, different VALUEs need to be produced for same tags combination, which requires us to have different keys while abiding to principles described in [KEYs](#keys) section. In that case, users must add an extra *specializer* tag (eg: MD5 checksum of that query) when creating key:

```php
$object = new Lucinda\DB\Key(["users", "roles", md5($query)]);
$key = $object->getValue(); // key will be "54ed347f362bb056e4d6db0477bf19c9_roles_users"
```

### Values

A value is a JSON-ed representation of [DATA](#data) saved on disk as a **.json** file named by KEY within [SCHEMA](#schema) folder according to following rules:

- [DATA](#data) must be json encodable otherwise an exception is going to be thrown

Value operations are encapsulated by **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** class. Usage example:

```php
$key = new Lucinda\DB\Key(["users", "roles"]); // initializes KEY
$value = new Lucinda\DB\Value("/usr/local/share/db", $key->getValue()); // instances VALUE
$value->set($data); // saves DATA by KEY, creating a KEY.json file within SCHEMA
```

#### Locking

A complicated problem in all databases is managing race conditions. What happens when a increment or decrement operation is ran at same time by different app users? Let's imagine this scenario when $data above was 8 increment is attempted at moment Z:

```php
# user X increments value at moment Z
$value->increment(1);
# user Y increments value at same moment Z
$value->increment(1);
```

Will end result be 10, as expected? The answer is no, because both processes got 8 to increment at same time! To solve such issues, file locks are used: on increment/decrement, file is locked for writes and unlocked only when value update completes. If a concurrent process tries to write to a still locked file, a **[Lucinda\DB\LockException](https://github.com/aherne/lucinda_db/blob/master/src/LockException.php)** is thrown. Developers can catch it and retry after delay:

```php
try {
  $value->increment(1);
} catch(Lucinda\DB\LockException $e) {
  usleep(100);
  $value->increment(1);
}
```

#### Load Balancing



### Schema

A schema is simply the folder where VALUEs are saved. A schema can be a single folder or an array of replicas that can be located on different disks from same server or even different servers (eg: via symlinks). Class **[Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php)** encapsulates operations one can perform on a single schema.

#### Distribution

For performance, consistency or scalability reasons, users can opt for SCHEMAs to be load balanced and evenly distributed. This is done via **[Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php)** that insures all schema operations are automatically reflected in replicas.

Both **[Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php)** and **[Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php)** abide to a single contract defining blueprints of SCHEMA operations through interface **[Lucinda\DB\SchemaOperations](https://github.com/aherne/lucinda_db/blob/master/src/SchemaOperations.php)**.

#### Maintenance

Modern operating systems allow up to 4,294,967,295 files in one folder but you shouldn't go anywhere near that value! Just like MySQL running out of disk space, LucindaDB may also run out of entries in SCHEMA when specialization is used at massive scale. If such a situation exists for your project, **[Lucinda\DB\DatabaseMaintenance](https://github.com/aherne/lucinda_db/blob/master/src/DatabaseMaintenance.php)** class was created, encapsulating most common maintenance operations:

- checking schemas health
- plugging schemas in/out
- deleting entries (files) older than chosen modified time
- deleting entries (files) whose name (KEY) matches TAG
- deleting entries (files) up to a limit if max capacity is reached

This class should be used via a cron job whose periodicity depends on the chance of your project to get filled! Example:

```php
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

Where:

- **XML_CONFIGURATION_PATH**: location of XML file configuring LucindaDB (see below)
- **DEVELOPMENT_ENVIRONMENT**: name of current development environment


## Reference guide


### Key

Class **[Lucinda\DB\Key](https://github.com/aherne/lucinda_db/blob/master/src/Key.php)** encapsulates creation of keys based on tags and deefines following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string[] tags | void | Compiles key based on [TAG](#tag)s. Throws Lucinda\DB\KeyException](https://github.com/aherne/lucinda_db/blob/master/src/KeyException.php) if [TAG](#tag)s break naming rules |
| getValue |  | string | Gets entry key compiled above. |

### ValueOperations

Interface defines operations one can perform on a database entry via following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| set | mixed $value | void | Sets entry value |
| get | void | mixed | Gets entry value |
| exists | void | bool | Checks if entry exists |
| increment | int $step = 1 | int | Increments **existing** entry value. Throws [Lucinda\DB\LockException](https://github.com/aherne/lucinda_db/blob/master/src/LockException.php) on race condition! |
| decrement | int $step = 1 | int | Decrements **existing** entry value. Throws [Lucinda\DB\LockException](https://github.com/aherne/lucinda_db/blob/master/src/LockException.php) on race condition! |
| delete | void | void | Deletes entry |

Implemented by:

- [Lucinda\DB\ValueDriver](https://github.com/aherne/lucinda_db/blob/master/src/ValueDriver.php): used for distributed multi-environment applications
- [Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php): used for local development or testing purposes only

### SchemaOperations

Interface defines operations one can perform on a database schema via following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| create | void | bool | Creates schema(s) |
| exists | void | bool | Checks if schema(s) exist |
| getAll | void | string[] | Gets all KEYs in schema(s) |
| getByTag | string $tag | string[] | Gets all KEYs in schema(s) matching TAG |
| getCapacity | void | int | Gets number of entries in schema(s) |
| deleteAll | void | int | Deletes all entries in schema(s) |
| drop | void | bool | Drops all schema(s), deleting all entries in the process |

Implemented by:

- [Lucinda\DB\SchemaDriver](https://github.com/aherne/lucinda_db/blob/master/src/SchemaDriver.php): used for distributed multi-environment applications
- [Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php): used for local development or testing purposes only

### DatabaseMaintenance

Class implements most common operations to maintain a distributed schema:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| __construct | string $xmlFilePath, string $developmentEnvironment | void | Sets location of [configuration](#configuration) file along with development environment for later querying |
| checkHealth | float $maximumWriteDuration | [Lucinda\DB\SchemaStatus](https://github.com/aherne/lucinda_db/blob/master/src/SchemaStatus.php)[string] | Performs health checks of all load balanced schemas and returns results as status by schema. |
| plugIn | string $schema | void | Plugs in schema to load-balanced DB without down times |
| plugOut | string $schema | void | Plugs out schema from load-balanced DB without down times |
| deleteByTag | string $tag | int | Deletes all DB entries whose key matches tag |
| deleteUntil | int $startTime | int | Deletes all DB entries whose last modified time is earlier than input |
| deleteByCapacity | int $minCapacity, int $maxCapacity | int | Deletes all DB entries by keeping schema at fixed max capacity range based on entry last modified time. |
