<?php
namespace Lucinda\DB;

/**
 * Enum defining possible algorithms of schema management:
 * - distributed: entry will be persisted on random slave (promoting load balancing)
 * - mirrored: entry will be persisted on every slave (promoting high availability)
 */
class SchemaDistribution
{
    const MIRRORED = "mirrored";
    const DISTRIBUTED = "distributed";
}
