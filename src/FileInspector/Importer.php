<?php

namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Encapsulates an importer that populates a schema based on an existing one
 */
class Importer implements FileInspector
{
    private string $destinationSchema;

    public function __construct(string $destinationSchema)
    {
        $this->destinationSchema = $destinationSchema;
    }

    /**
     * {@inheritDoc}
     *
     * @see \Lucinda\DB\FileInspector::inspect()
     */
    public function inspect(string $folder, string $file): void
    {
        if (!in_array($file, [".", ".."]) && !file_exists($this->destinationSchema."/".$file)) {
            copy($folder."/".$file, $this->destinationSchema."/".$file);
            touch($this->destinationSchema."/".$file, filemtime($folder."/".$file));
        }
    }
}
