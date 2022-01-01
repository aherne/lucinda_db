<?php
namespace Lucinda\DB;

/**
 * Blueprint of operations to execute on a database entry
 */
interface ValueOperations
{
    /**
     * Sets entry value
     *
     * @param mixed $value
     */
    public function set(mixed $value): void;
    
    /**
     * Gets existing entry value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @return mixed
     */
    public function get(): mixed;
    
    /**
     * Checks if entry exists
     *
     * @return bool
     */
    public function exists(): bool;
    
    /**
     * Increments existing entry and returns value
     *
     * @param int $step Step of incrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function increment(int $step = 1): int;
    
    /**
     * Decrements existing entry and returns value
     *
     * @param int $step Step of decrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function decrement(int $step = 1): int;
    
    /**
     * Deletes existing entry
     *
     * @throws KeyNotFoundException If entry doesn't exist
     */
    public function delete(): void;
}
