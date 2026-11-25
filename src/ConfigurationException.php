<?php

namespace Lucinda\DB;

/**
 * Signals missing or invalid database configuration or an invalid membership change
 *
 * Raised when XML cannot supply schemas, a plug operation names an unavailable or
 * inappropriately registered directory, or removal would leave no configured schema.
 * The message describes the configuration or membership precondition that failed.
 */
final class ConfigurationException extends \Exception
{
}
