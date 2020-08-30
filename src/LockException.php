<?php
namespace Lucinda\DB;

/**
 * Exception thrown when lock on requested key was already acquired by another process
 */
class LockException extends \Exception
{
}
