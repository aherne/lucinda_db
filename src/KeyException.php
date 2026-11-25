<?php

namespace Lucinda\DB;

/**
 * Signals that a canonical key or tag filter could not be constructed
 *
 * Key requires a nonempty list of tags containing only lowercase ASCII letters,
 * digits, and hyphens. Tag-based inspectors and deleters share the same validation.
 */
final class KeyException extends \Exception
{
}
