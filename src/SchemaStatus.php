<?php
namespace Lucinda\DB;

/**
 * Enum defining statuses for schemas on health checks
 */
interface SchemaStatus
{
    const ONLINE = 1; // schema is up and running
    const OFFLINE = 2; // schema is down
    const UNRESPONSIVE = 3; // schema is up, but not responsive
    const OVERLOADED = 4; // schema is up, but overloaded (operations take too long to execute)
}
