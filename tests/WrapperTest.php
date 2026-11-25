<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Wrapper;
use Lucinda\DB\ValueDriver;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\SchemaDriver;
use Lucinda\DB\ConfigurationException;
use Lucinda\DB\KeyException;
use Lucinda\UnitTest\Result;

/**
 * Verifies driver creation from caller-supplied XML without environment selection.
 */
final class WrapperTest extends TestCase
{
    /** Configured wrapper shared by the factory checks */
    private Wrapper $object;

    /**
     * Constructs the wrapper from the environment-independent XML fixture.
     */
    public function __construct()
    {
        $this->object = new Wrapper(simplexml_load_file(__DIR__."/tests.xml"));
    }

    /** @return Result Entry operations use the replicated value driver */
    public function getEntryDriver(): Result
    {
        return $this->assertInstanceOf(ValueDriver::class, $this->object->getEntryDriver(["a","b"]));
    }

    /** @return Result Schema operations use the replicated schema driver */
    public function getSchemaDriver(): Result
    {
        return $this->assertInstanceOf(SchemaDriver::class, $this->object->getSchemaDriver());
    }

    /** @return Result[] In-memory XML works and invalid configuration or tags are rejected */
    public function acceptsInMemoryXmlAndValidatesInputs(): array
    {
        $wrapper = new Wrapper(new \SimpleXMLElement(
            '<xml><lucinda_db><schemas><schema>/tmp/schema</schema></schemas></lucinda_db></xml>'
        ));
        $configurationError = null;
        try {
            new Wrapper(new \SimpleXMLElement('<xml/>'));
        } catch (ConfigurationException $exception) {
            $configurationError = $exception;
        }
        $keyError = null;
        try {
            $wrapper->getEntryDriver([]);
        } catch (KeyException $exception) {
            $keyError = $exception;
        }
        return [
            $this->assertInstanceOf(ValueDriver::class, $wrapper->getEntryDriver(['a'])),
            $this->assertInstanceOf(ConfigurationException::class, $configurationError),
            $this->assertInstanceOf(KeyException::class, $keyError)
        ];
    }
}
