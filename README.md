# Lucinda DB: Pure PHP Tag-Based Key-Value Store
 
Lucinda DB is a KEY-VALUE store originally designed to help developers cache results of resource-intensive SQL queries based to criteria (TAG) query depends on. It is different from other KV stores by having KEYs self-generate based on TAGs query result depended on and VALUEs saved as individual JSON files (instead of RAM) named by KEY in one/more SCHEMAs.

This approach brings following advantages: 

- **ability to work without a server**: a database specification that can be implemented in any programming language on any operating system
- *KEY standardization*: value of a KEY is generated according to a predictable rule based on value of TAGs it depends on
- *no entries duplication*: because combination of TAGs is always unique, 
- *ability to query by criteria*: something impossible in standard KV stores that purely rely on RAM hash tables
- *portability*: to transfer/backup database, it is as easy as copying schema folder(s)
- *scalability*: ability of database to be distributed on multiple disks in real time

and following disadvantages:

- *slightly reduced speed*: hard drives will always be slower than RAM, but considering how fast SSDs are this won't be a problem 
- *no expiration for entries": all entries inside, being separate files, persist forever unless specifically deleted
- *not suitable for temporary/volatile data": if your app expects database entries to be volatile (changing randomly), use standard KV stores instead
- *requires daemonized maintenance*: in case volatility is expected, a program must periodically remove old files in order prevent disk(s) getting full

Ultimately, it is always up for developers to decide which KV store model fits their application the best! More often than not you will need to employ multiple stores (eg: LucindaDB and Redis) to cover all usage case (one for persistent queryable data, the other for volatile data).


## Key Concepts

As stated in introduction text, Lucinda DB relies on following concepts:

- TAGs: each tag being the criteria based on whom key name was generated
- KEYs: a combination of TAGs
- VALUEs:
- SCHEMAs:

## Tags

Each TAG consists of a mandatory name (eg: "users") and one or more optional specializations (eg: "12") separated by "-" sign (eg: users-12). Both names and specializations can only contain alphanumeric characters! The more you specialize, the larger the database, so **specialization should be avoided** whenever possible.

## Keys

Each KEY consists of a combination of TAGs it depends on. To make things easier for maintenance, each finite combination of TAGs results into a single KEY, regardless of how they were ordered by caller! The rules based on whom KEY name is calculated are:

- sorts TAGs alphabetically
- joins all TAGS using "_" sign into a lowercase form

If, for example, caller asked to get a value based on TAGs 'users' and 'roles', engine will first compile KEY name as 'roles_users' according to rules above then return json decoded contents of 'roles_users.json' file found in SCHEMA folder.

## Values

Each VALUE saved will be json encoded and served in json decoded form. This naturally means data you're attempting to save must be json encodable, otherwise an exception is going to be thrown. In certain situations (such as increment/decrement), VALUEs are automatically synchronized (protected by a MUTEX lock) so their update remains consistent in case of concurrency. If lock was already acquired by a different process, no wait will occur and an exception is thrown.

## Schema
 
Each file in KEY-VALUE store is saved as a ".json" file in a SCHEMA folder with alphanumeric name. Modern operating systems allow up to 4,294,967,295 files in a folder but you shouldn't go anywhere near! Just like MySQL running out of disk space, LucindaDB may run out of entries in SCHEMA when specialization is used at enormous scale. A paralel process should keep database maintained and expunge older entries to make sure software limit of 1 billion files max is never exceeded.