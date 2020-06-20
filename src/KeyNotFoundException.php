<?php
namespace Lucinda\DB;

/**
 * Exception thrown when an entry operation required key to exist already was executed and key was not found in DB
 */
class KeyNotFoundException extends \Exception
{
    
}