<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Configuration;
use Lucinda\DB\ConfigurationException;
use Lucinda\UnitTest\Result;

/**
 * Verifies schema extraction from caller-supplied, environment-independent XML.
 */
final class ConfigurationTest extends TestCase
{
    /**
     * Checks schema order and support for a single schema and the shared fixture.
     *
     * @return Result[] Configuration parsing assertions
     */
    public function getSchemas(): array
    {
        $configuration = new Configuration(simplexml_load_file(__DIR__."/tests.xml"));
        $single = new Configuration(new \SimpleXMLElement(
            '<xml><lucinda_db><schemas><schema>/tmp/schema</schema></schemas></lucinda_db></xml>'
        ));
        return [
            $this->assertEquals(["tests/myClient1", "tests/myClient2"], $configuration->getSchemas()),
            $this->assertEquals(["/tmp/schema"], $single->getSchemas())
        ];
    }

    /**
     * Rejects missing configuration, empty schema lists, and the old layout.
     *
     * @return Result[] Invalid-configuration assertions
     */
    public function rejectsInvalidConfiguration(): array
    {
        $results = [];
        foreach ([
            '<xml/>',
            '<xml><lucinda_db><schemas/></lucinda_db></xml>',
            '<xml><lucinda_db><local><schemas><schema>/tmp/schema</schema></schemas></local></lucinda_db></xml>'
        ] as $source) {
            $exception = null;
            try {
                new Configuration(new \SimpleXMLElement($source));
            } catch (ConfigurationException $error) {
                $exception = $error;
            }
            $results[] = $this->assertInstanceOf(ConfigurationException::class, $exception);
        }
        return $results;
    }
}
