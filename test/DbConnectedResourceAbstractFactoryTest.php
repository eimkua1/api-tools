<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\DbConnectedResource;
use Laminas\ApiTools\DbConnectedResourceAbstractFactory;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DbConnectedResourceAbstractFactoryTest extends TestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        $this->services = $this->prophesize(ContainerInterface::class);
        $this->factory  = new DbConnectedResourceAbstractFactory();
    }

    public function testWillNotCreateServiceIfConfigServiceMissing()
    {
        $this->services->has('config')->willReturn(false);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigMissing()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn([]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigIsNotAnArray()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => 'invalid']);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigDoesNotHaveDbConnectedSegment()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['foo' => 'bar']]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfDbConnectedSegmentDoesNotHaveRequestedName()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
           ->willReturn([
               'api-tools' => [
                   'db-connected' => [
                       'bar' => 'baz',
                   ],
               ],
           ]);
        $this->services->has('Foo\Table')->willReturn(false);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    /**
     * @return array
     */
    public function invalidConfig()
    {
        return [
            'invalid_table_service' => [['table_service' => 'non_existent']],
            'invalid_virtual_table' => [[]],
        ];
    }

    /**
     * @param array $configForDbConnected
     * @dataProvider invalidConfig
     */
    public function testWillNotCreateServiceIfDbConnectedSegmentIsInvalidConfiguration($configForDbConnected)
    {
        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => $configForDbConnected,
                ],
            ],
        ];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);

        if (isset($configForDbConnected['table_service'])) {
            $this->services->has($configForDbConnected['table_service'])->willReturn(false);
        } else {
            $this->services->has('Foo\Table')->willReturn(false);
        }

        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    /**
     * @return array[]
     */
    public function validConfig()
    {
        return [
            'table_service' => [['table_service' => 'foobartable'], 'foobartable'],
            'virtual_table' => [[], 'Foo\Table'],
        ];
    }

    /**
     * @param array $configForDbConnected
     * @param string $tableServiceName
     * @dataProvider validConfig
     */
    public function testWillCreateServiceIfDbConnectedSegmentIsValid($configForDbConnected, $tableServiceName)
    {
        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => $configForDbConnected,
                ],
            ],
        ];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);
        $this->services->has($tableServiceName)->willReturn(true);

        $this->assertTrue($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    /**
     * @param array $configForDbConnected
     * @param string $tableServiceName
     * @dataProvider validConfig
     */
    public function testFactoryReturnsResourceBasedOnConfiguration($configForDbConnected, $tableServiceName)
    {
        $tableGateway = $this->prophesize(TableGateway::class)->reveal();
        $this->services->has($tableServiceName)->willReturn(true);
        $this->services->get($tableServiceName)->willReturn($tableGateway);

        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => $configForDbConnected,
                ],
            ],
        ];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);

        $resource = $this->factory->__invoke($this->services->reveal(), 'Foo');
        $this->assertInstanceOf(DbConnectedResource::class, $resource);
    }
}
