<?php

namespace Lucinda\DB;

/**
 * Exception thrown when lock on requested key was already acquired by another process
 */
final class LockException extends \Exception
{
}
