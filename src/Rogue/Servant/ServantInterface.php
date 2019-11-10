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
 * Servant Interface
 * 
 * @author Matthias Kaschubowski <nihylum@gmail.com>
 * @api 1.0
 */
interface ServantInterface {

    /**
     * gets a serviced object
     * 
     * @param string $key name of the service
     * @throws ServantException
     * @return object
     */
    public function get(string $key): object;

    /**
     * checks whether the given key for a serviced object does exists or not.
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * composes a service into a key using the provided interface and an optional
     * service initializer closure.
     * 
     * @param string $key
     * @param string $interface
     * @param null|callable $closure
     * @return ServiceInterface
     */
    public function compose(string $key, string $interface, callable $closure = null): ServiceInterface;

    /**
     * wires a service to a given interface and an optional service initializer closure. 
     * Interfaces set by this method are considered for autowiring.
     * 
     * @param string $interface
     * @param null|callable $closure
     * @return ServiceInterface
     */
    public function wire(string $interface, callable $closure = null): ServiceInterface;

    /**
     * orchestrates a interface by the given interface name and optional constructor parameters.
     * This method must also create classes when the class name is not supported by the servant.
     * 
     * @param string $interface
     * @param mixed[] $params
     * @return object
     */
    public function make(string $interface, ... $params): object;

    /**
     * checks whether a given interface is supported by the servant.
     * 
     * @param string $interface
     * @return bool
     */
    public function supports(string $interface): bool;

    /**
     * removes the service from the servant for the given interface. Must not lock the interface
     * as not set when the servant is a fork.
     * 
     * @param string $interface
     * @return void
     */
    public function forget(string $interface): void;

    /**
     * removes the service key from the servant for the given interface. Must not lock the interface key
     * as not set when the servant is a fork.
     * 
     * @param string $key
     * @return void
     */
    public function remove(string $key): void;

    /**
     * drops the object instance of the given interface. Must not lock the interface as not set when
     * the servant is a fork.
     * 
     * @param string $key
     * @return void
     */
    public function dropInstanceOfInterface(string $interface): void;

    /**
     * drops the object instance of the given key. Must not lock the interface as not set when the
     * servant is a fork.
     */
    public function dropInstanceOfService(string $key): void;

    /**
     * forks the servant and makes all interfaces of the forked servant available to the fork.
     * The fork can overwrite services without touching the forked servant.
     * 
     * @return ServantInterface
     */
    public function fork(): ServantInterface;

    /**
     * checks whether this Servant is a fork or not.
     * 
     * @return bool
     */
    public function isFork(): bool;

    /**
     * calls the provided callback.
     * 
     * @param callable $callback
     * @param array $params
     * @return void|mixed
     */
    public function call(callable $callback, array $params);

    /**
     * returns the reflection cache instance.
     * 
     * @return null|ReflectionCacheInterface
     */
    public function getReflectionCache(): ? ReflectionCacheInterface;

}
