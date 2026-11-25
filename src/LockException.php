<?php

namespace Lucinda\DB;

/**
 * Signals failure to open or immediately acquire an entry mutation mutex
 *
 * File uses nonblocking exclusive flock rather than waiting for another mutation.
 * Callers may retry according to their own contention policy. This exception can
 * also indicate that the temporary-directory mutex file could not be opened.
 */
final class LockException extends \Exception
{
}
