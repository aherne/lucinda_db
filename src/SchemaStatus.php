<?php

namespace Lucinda\DB;

/**
 * Identifies the result of one maintenance health probe
 *
 * DatabaseMaintenance returns these integer-backed cases keyed by schema path.
 * The write-duration threshold is supplied by the caller. Exceptions raised while
 * probing are propagated rather than represented by an additional enum case.
 *
 * @see DatabaseMaintenance::checkHealth()
 */
enum SchemaStatus: int
{
    /**
     * The directory is writable and the probe completed within the write threshold.
     */
    case ONLINE = 1;
    /**
     * The configured directory is absent or not writable.
     */
    case OFFLINE = 2;
    /**
     * The probe could not be found or its decoded value differed from the expected value.
     */
    case UNRESPONSIVE = 3;
    /**
     * The probe succeeded but its measured write duration exceeded the threshold.
     */
    case OVERLOADED = 4;
}
