<?php

namespace Lucinda\DB;

/**
 * Defines blueprints of entry inspection algorithm
 */
interface FileInspector
{
    /**
     * Inspects entry in DB
     *
     * @param string $folder Schema folder containing file to inspect
     * @param string $file   File to inspect
     */
    public function inspect(string $folder, string $file): void;
}
