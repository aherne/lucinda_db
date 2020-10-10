but it also comes with a few disadvantages:

- *slightly reduced speed*: hard drives will always be slower than RAM, but considering how fast SSDs are this won't be a problem unless your app has high thoroughput
- *no expiration for entries*: all entries inside, being separate files, persist forever unless specifically deleted
- *not suitable for temporary/volatile data*: if your app expects database entries to be volatile (changing randomly), use standard RAM-based KV stores are best
- *requires daemonized maintenance*: in case volatility is expected, a program must periodically remove old files in order prevent disk(s) getting full

It is always up for developers to decide which KV store model fits their application the best! More often than not you will need to employ multiple stores (eg: LucindaDB and Redis) to cover all usage cases (one for persistent query-able data, the other for volatile data).