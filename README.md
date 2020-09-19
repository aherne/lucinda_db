# Lucinda DB: Pure PHP Tag-Based Key-Value Store
 
Lucinda DB is serverless KEY-VALUE store originally designed to help developers cache results of resource-intensive SQL queries based to criteria (TAG) query depends on. It is different from other KV stores by having KEYs self-generate based on TAGs query result depended on and VALUEs saved as individual JSON files (instead of RAM) named by KEY in one/more SCHEMAs.

This approach brings following advantages: 

- **ability of working without a server**: operating system on host machine, already optimized to manipulate files, becomes the server
- **ability of being platform agnostic**: a database specification that can be implemented in any programming language on any operating system
- *KEY standardization*: value of a KEY is generated according to a predictable rule based on value of TAGs it depends on
- *no entries duplication*: because combination of TAGs is always unique, 
- *ability to query by criteria*: something impossible in standard KV stores that purely rely on RAM hash tables
- *portability*: to transfer/backup database, it is as easy as copying schema folder(s)
- *scalability*: ability of database to be distributed on multiple disks in real time

and following disadvantages:

- *slightly reduced speed*: hard drives will always be slower than RAM, but considering how fast SSDs are this won't be a problem unless your app has high thoroughput
- *no expiration for entries*: all entries inside, being separate files, persist forever unless specifically deleted
- *not suitable for temporary/volatile data*: if your app expects database entries to be volatile (changing randomly), use standard KV stores instead
- *requires daemonized maintenance*: in case volatility is expected, a program must periodically remove old files in order prevent disk(s) getting full

Ultimately, it is always up for developers to decide which KV store model fits their application the best! More often than not you will need to employ multiple stores (eg: LucindaDB and Redis) to cover all usage cases (one for persistent queryable data, the other for volatile data).

## Fundamental Concepts

As stated in introduction text, in order to understand how Lucinda DB works, following concepts must be made clear:

- *DATA*: this is value of data to cache (eg: result of your SQL query or query combinations)
- *TAGs*: criteria based on whome DATA was generated (eg: "users", "roles")
- *KEYs*: the key in KV store, whose name was generated automatically based on TAGs it depends on (eg: "roles_users")
- *VALUEs*: the value in KV store present as json-ed DATA saved on disk as a separate file in SCHEMA folder named by KEY
- *SCHEMAs*: folders/disks in which KV entries are stored

### Data

This is result to cache as VALUE in KV store, convertible to JSON. Example results of query:

```sql
SELECT t1.name AS user, t3.name AS role
FROM users AS t1
INNER JOIN users__roles AS t2 ON t1.user_id = t2.user_id
INNER JOIN roles AS t3 ON t2.role_id = t3.id
WHERE t1.active = 1
```

Processed into following PHP array structure:

```php
["(user)"=>["(role)",...], ...]
```

### Tags

A tag corresponds to name of criteria based on whom DATA was generated (eg: "users", "roles" for above). A tag's value must obey following requirements:

- must be lowercase
- can only contain a-z0-9 characters
- "-" sign is allowed as separator of multi-word names

### Keys


A key is unique identifier of DATA in KV store named by combination of TAGs data depended on (eg: "roles_users" above) . To make things easier for maintenance, each finite combination of TAGs results into a single KEY, regardless of how they were ordered by caller! The rules based on whom KEY name is calculated are:

- checks if TAGs obey above specifications
- sorts TAGs alphabetically
- joins all TAGS using "_" sign

Key creation is encapsulated by **[Lucinda\DB\Key](https://github.com/aherne/lucinda_db/blob/master/src/Key.php)** class. Usage example:

```php
$object = new Lucinda\DB\Key(["users", "roles"]);
$key = $object->getValue(); // key will be "roles_users"
```

What happens if different VALUEs need to be produced for same tags combination, as for instance in results of a different query involving users and roles:

```sql
SELECT t3.name AS role
FROM users AS t1
INNER JOIN users__roles AS t2 ON t1.user_id = t2.user_id
INNER JOIN roles AS t3 ON t2.role_id = t3.id
WHERE t1.id = 12
```

Processed into PHP array:
```php
["(role"), "(role)"]
```

Even though query also  only involved users and roles, its semantic was different and so was processing. In that case, users must add an extra *specializer* tag (eg: MD5 checksum of that query) when creating key:


```php
$object = new Lucinda\DB\Key(["users", "roles", md5($query)]);
$key $object->getValue(); // key will be "54ed347f362bb056e4d6db0477bf19c9_roles_users"
```

### Values

A value is a JSON-ed representation of DATA saved on disk saved as a .json file named by KEY within SCHEMA folder according to following rules:

- VALUE is compiled by JSON encoding DATA. This naturally means data you're attempting to save must be json encodable, otherwise an exception is going to be thrown.
- VALUE is saved as a file named by KEY within SCHEMA folder whose name is KEY and extension is ".json"

Value operations are encapsulated by **[Lucinda\DB\Value](https://github.com/aherne/lucinda_db/blob/master/src/Value.php)** class. Usage example:

```php
$key = new Lucinda\DB\Key(["users", "roles"]); // initializes KEY
$value = new Lucinda\DB\Value("/usr/local/share/db", $key->getValue()); // instances VALUE
$value->set($data); // saves DATA by KEY, creating a KEY.json file within SCHEMA
```

A complicated problem in all databases is concurrency management. What happens when a increment or decrement operation is ran at same time by different app users (race condition)? If concurrency isn't managed, only one will increment so the end result will be inconsistent. To address this issue, MUTEX locks are used: if a lock is acquired by an user on that KEY, the other will wait until former completes. If, however, on second attempt lock is still not released by previous process an exception is thrown!

### Schema
 
A schema is simply the folder where VALUEs are saved. It obeys following rules and brings following features:

- name must be alphanumeric
- storable on different disks (to promote concurrency and scalability) or even on different servers (eg: via symlinks)

Modern operating systems allow up to 4,294,967,295 files in one folder but you shouldn't go anywhere near that value! Just like MySQL running out of disk space, LucindaDB may also run out of entries in SCHEMA when specialization is used at massive scale. If such a situation exists for your project, Lucinda\DB\Schema class was created, encapsulating most common maintainance operations:

- deleting entries (files) older than chosen modified time
- deleting entries (files) whose name (KEY) matches TAG
- deleting entries (files) up to a limit if max capacity is reached

This class should be used via a cron job whose periodicity depends on the chance of your project to get filled! Usage example of daily cleanup cron:

```php
$schema = new Lucinda\DB\Schema("/usr/local/share/db"); // instances VALUE
$schema->deleteUntil(time()-(3600*24)); // deletes all files older than one day ago
```

In addition to deletion algorithms, **[Lucinda\DB\Schema](https://github.com/aherne/lucinda_db/blob/master/src/Schema.php)** allows you to fast find files, but indexing is recommended
