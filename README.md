# Lucinda DB: Pure PHP Tag-Based Key-Value Store
 
One of greatest inconveniences in key-value stores (eg: Redis) is simply that they are not relational. To search for ONE key, you will need to know its FULL name then everything is fine. What happens if you only know A PART of that key or want to search for MORE THAN ONE keys at once matching pattern? Key-value stores do not have a general answer for this problem, since that is not what they were designed for. They all work as RAM-based hash tables insuring very fast O(1*) complexity for inserting or deleting a key plus an ability (in most vendors) of periodically persisting entries on disk to restore in case server gets restarted. Partial searches will typically result into extremely slow O(N) operations that require traversal of entire hash table.

This API follows a completely different approach! It thinks of a key as an intersection of edges in a directed multidimensional graph, each vertex coresponding to a criteria (aka TAG) based on whom key was calculated. VALUE of KEY, whose name corresponds to list of TAGs it depends on, will be stored as an individual json file inside a SCHEMA folder. This allows not only O(1) insertion/deletion (because OS knows where to look for already), but also fast searches of values in store by tag names through graph traversal algorithms.

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