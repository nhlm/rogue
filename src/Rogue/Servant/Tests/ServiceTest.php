<?php
/**
 * This file is part of the Rogue PHP Universe.
 * 
 * (c) 2019 Matthias Kaschubowski
 * 
 * @package rogue.servant
 */
namespace Rogue\Servant\Tests;

use PHPUnit\Framework\TestCase;
use Rogue\Servant\Service;
use Rogue\Servant\ServiceInterface;
use Rogue\Servant\ReflectionCache;
use Rogue\Servant\ServantException;
use DateTimeInterface;
use DateTime;

class ServantTests extends TestCase {

    protected $reflectionCache;

    public function setUp(): void
    {
        $this->reflectionCache = new ReflectionCache();
    }

    public function testServiceInstancing(): Service
    {
        $service = new Service(DateTimeInterface::class, $this->reflectionCache);

        $this->assertInstanceOf(ServiceInterface::class, $service);
        $this->assertInstanceOf(Service::class, $service);

        $this->assertEquals(DateTimeInterface::class, $service->getInterface());
        $this->assertEquals(DateTimeInterface::class, $service->getConcrete());

        return $service;
    }

    /**
     * @depends testServiceInstancing
     */
    public function testServiceConcreteAssignment(Service $service): void
    {
        $service->withConcrete(DateTime::class);

        $this->assertEquals(DateTimeInterface::class, $service->getInterface());
        $this->assertEquals(DateTime::class, $service->getConcrete());
    }

    /**
     * @depends testServiceInstancing
     */
    public function testServiceConcreteAssignmentFailure(Service $service): void
    {
        $this->expectException(ServantException::class);

        $service->withConcrete(\stdClass::class);
    }

    /**
     * @depends testServiceInstancing
     */
    public function testServiceFactoryPlain(Service $service): void
    {
        $this->assertNull($service->getFactory());
    }

    public function testServiceFactorySuccess(): Service
    {
        $service = new Service(DateTimeInterface::class, $this->reflectionCache);

        $service->withFactory(
            function(): DateTimeInterface {
                return new DateTime();
            }
        );

        $this->assertEquals(DateTimeInterface::class, $service->getConcrete());
        $this->assertTrue($service->hasFactory());
        
        return $service;
    }

    /**
     * @depends testServiceFactorySuccess
     */
    public function testServiceFactoryFailure(Service $service): void
    {
        $this->expectException(ServantException::class);

        $service->withFactory(
            function() {
                return new DateTime();
            }
        );
    }

    public function testServiceInitializerSuccess(): Service
    {
        $service = new Service(DateTimeInterface::class, $this->reflectionCache);

        $this->assertNull($service->getInitializer());

        $service->withInitializer(
            function(DateTimeInterface $dateTime): DateTimeInterface {
                $dateTime->modify('now');

                return $dateTime;
            }
        );

        $this->assertIsCallable($service->getInitializer());
        $this->assertTrue($service->hasInitializer());

        return $service;
    }

    /**
     * @depends testServiceInitializerSuccess
     */
    public function testServiceInitializerWrongParameterCountFailure(Service $service): void
    {
        $this->expectException(ServantException::class);

        $service->withInitializer(
            function() {

            }
        );
    }

    /**
     * @depends testServiceInitializerSuccess
     */
    public function testServiceInitializerIncompatibleFirstParameterFailure(Service $service): void
    {
        $this->expectException(ServantException::class);

        $service->withInitializer(
            function(\stdClass $foo) {

            }
        );
    }

    public function testServiceSingleton(): void
    {
        $service = new Service(DateTimeInterface::class, $this->reflectionCache);

        $this->assertFalse($service->mustBeSingleton());

        $service->singleton();

        $this->assertTrue($service->mustBeSingleton());

        $service->singleton(false);

        $this->assertFalse($service->mustBeSingleton());

        $service->singleton(true);

        $this->assertTrue($service->mustBeSingleton());
    }
}
