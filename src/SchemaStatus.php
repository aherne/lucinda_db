<?php

namespace Lucinda\DB;

/**
 * Enum defining statuses for schemas on health checks
 */
enum SchemaStatus: int
{
    case ONLINE = 1; // schema is up and running
    case OFFLINE = 2; // schema is down
    case UNRESPONSIVE = 3; // schema is up, but not responsive
    case OVERLOADED = 4; // schema is up, but overloaded (operations take too long to execute)
}
