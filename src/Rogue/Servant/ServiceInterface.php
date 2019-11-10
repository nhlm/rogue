<?php declare(strict_types=1);
/**
 * This file is part of the Rogue PHP Universe.
 * 
 * (c) 2019 Matthias Kaschubowski
 * 
 * @package rogue.servant
 */
namespace Rogue\Servant;

/**
 * Service Interface
 * 
 * @author Matthias Kaschubowski <nihylum@gmail.com>
 * @api 1.0
 */
interface ServiceInterface {

    /**
     * retruns the interface/class name of the service
     * 
     * @return string
     */
    public function getInterface(): string;

    /**
     * retruns the concrete of the interface, if no concrete is set the interface name of the service
     * will be returned.
     * 
     * @return string
     */
    public function getConcrete(): string;

    /**
     * assigns a new classname as a concrete to the service.
     * 
     * @param string $className
     * @throws ServantException
     * @return ServiceInterface
     */
    public function withConcrete(string $className): ServiceInterface;

    /**
     * returns the factory or null if no factory was set.
     * 
     * @return null|callable
     */
    public function getFactory(): ? callable;

    /**
     * assigns a factory callable as a concrete to the service. The assigned factory callable must
     * serve a return type that is compatible to the service interface.
     * 
     * @param callable $typedFactory
     * @throws ServantException
     * @return ServiceInterface
     */
    public function withFactory(callable $typedFactory): ServiceInterface;

    /**
     * checks whether a factory is present or not.
     * 
     * @return bool
     */
    public function hasFactory(): bool;

    /**
     * returns the initializer or null if no initializer is present.
     * 
     * @return null|callable
     */
    public function getInitializer(): ? callable;

    /**
     * assigns a initializer that will be called after the object is constructed. The initilizer will
     * recive the create object as its first argument, all other arguments of the initilizer will be
     * auto-wired.
     * 
     * @param callable $callback
     * @throws ServantException
     * @return ServiceInterface
     */
    public function withInitializer(callable $callback): ServiceInterface;

    /**
     * checks whether an initializer has been assigned or not.
     * 
     * @return bool
     */
    public function hasInitializer(): bool;

    /**
     * controls the singleton flag of the service. If set to true, the responsible servant must cache
     * the instance and deliver the cached instance when the service will be wired/fetched in the future.
     * 
     * @param bool $flag
     * @return ServiceInterface
     */
    public function singleton(bool $flag = true): ServiceInterface;

    /**
     * checks whether the resulting instance must be a singleton or not.
     * 
     * @return bool
     */
    public function mustBeSingleton(): bool;

}
