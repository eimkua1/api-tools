<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Laminas\ApiTools\Autoloader;
use LaminasTest\ApiTools\TestAsset\Foo_Bar;
use LaminasTest\ApiTools\TestAsset\Foo_Bar\Baz_Bat;
use PHPUnit\Framework\TestCase;

use function class_exists;

class AutoloaderTest extends TestCase
{
    /**
     * @return string[][]
     */
    public function classesToAutoload()
    {
        return [
            'Foo_Bar'         => [Foo_Bar::class],
            'Foo_Bar\Baz_Bat' => [Baz_Bat::class],
        ];
    }

    /**
     * @param string $className
     * @dataProvider classesToAutoload
     */
    public function testAutoloaderDoesNotTransformUnderscoresToDirectorySeparators($className)
    {
        $autoloader = new Autoloader([
            'namespaces' => [
                'LaminasTest\ApiTools\TestAsset' => __DIR__ . '/TestAsset',
            ],
        ]);
        $result     = $autoloader->autoload($className);
        $this->assertFalse(false === $result);
        $this->assertTrue(class_exists($className, false));
    }
}
