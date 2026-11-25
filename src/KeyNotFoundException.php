<?php

namespace Lucinda\DB;

/**
 * Signals that an operation requiring an existing entry found no usable key
 *
 * Value throws this exception for missing reads, counters, and deletion. A
 * ValueDriver can tolerate missing individual replicas; its operation reports
 * absence according to the selected operation's replica requirements.
 */
final class KeyNotFoundException extends \Exception
{
}
