<?php declare(strict_types=1);
/**
 * This file is part of the Rogue PHP Universe.
 * 
 * (c) 2019 Matthias Kaschubowski
 * 
 * @package rogue.servant
 */
namespace Rogue\Servant;

use SplObjectStorage;
use ReflectionParameter;
use Generator;

/**
 * Servant Class
 * 
 * @author Matthias Kaschubowski <nihylum@gmail.com>
 * @api 1.0
 */
class Servant implements ServantInterface {

    /**
     * @var ServantInterface
     */
    protected $origin;

    /**
     * @var ReflectionCacheInterface
     */
    protected $cache;

    /**
     * @var ServiceInterface[]
     */
    protected $services = [];

    /**
     * @var ServiceInterface[]
     */
    protected $interfaces = [];

    /**
     * @var SplObjectStorage
     */
    protected $instances;

    /**
     * The constructor.
     * 
     * @param null|ServantInterface $origin
     * @param null|ReflectionCacheInterface $cache
     */
    public function __construct(ServantInterface $origin = null, ReflectionCacheInterface $cache = null)
    {
        if ( $orgin instanceof ServantInterface ) {
            $this->origin = $origin;
            $this->cache = $cache ?? $this->origin->getReflectionCache() ?? new ReflectionCache();
        }

        $this->instances = new SplObjectStorage();
    }

    /**
     * gets a serviced object
     * 
     * @param string $key name of the service
     * @throws ServantException
     * @return object
     */
    public function get(string $key): object
    {
        if ( ! $this->has($key) && $this->isFork() && $this->origin->has($key) ) {
            return $this->origin->get($key);
        }

        if ( ! $this->has($key) ) {
            throw new ServantException(
                'Unknown key: '.$key
            );
        }

        $service = $this->services[$key];

        if ( $service->mustBeSingleton() && $this->instances->contains($service) ) {
            return $this->instances[$service];
        }

        $object = $this->build($service);

        if ( $service->mustBeSingleton() ) {
            $this->instances->attach($service, $object);
        }

        return $object;
    }

    /**
     * checks whether the given key for a serviced object does exists or not.
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->services);
    }

    /**
     * composes a service into a key using the provided interface and an optional
     * service initializer closure.
     * 
     * @param string $key
     * @param string $interface
     * @param null|callable $closure
     * @return ServiceInterface
     */
    public function compose(string $key, string $interface, callable $closure = null): ServiceInterface
    {
        $this->services[$key] = new Service($interface);

        if ( is_callable($callable) ) {
            $this->call($closure, [$this->services[$key]]);
        }

        return $this->services[$key];
    }

    /**
     * wires a service to a given interface and an optional service initializer closure. 
     * Interfaces set by this method are considered for autowiring.
     * 
     * @param string $interface
     * @param null|callable $closure
     * @return ServiceInterface
     */
    public function wire(string $interface, callable $closure = null): ServiceInterface
    {
        $this->interfaces[$interface] = new Service($interface);

        if ( is_callable($callable) ) {
            $this->call($closure, [$this->interfaces[$interface]]);
        }

        return $this->interfaces[$interface];
    }

    /**
     * orchestrates a interface by the given interface name and optional constructor parameters.
     * This method must also create classes when the class name is not supported by the servant.
     * 
     * @param string $interface
     * @param mixed[] $params
     * @return object
     */
    public function make(string $interface, ... $params): object
    {
        $service = null;

        if ( $this->supports($interface) ) {
            $service = $this->interfaces[$interface];
        }

        if ( null === $service ) {
            if ( ! class_exists($interface, true) ) {
                throw new ServantInterface(
                    'Provided interface is not supported and can not be auto-wired because it is not class: '. 
                    $interface
                );
            }

            $service = new Service($interface);
        }

        if ( $service->mustBeSingleton() && $this->cache->contains($service) ) {
            return $this->cache[$service];
        }

        $object = $this->build($cache, $params);

        if ( $service->mustBeSingleton() ) {
            $this->cache->attach($service, $object);
        }

        return $object;
    }

    /**
     * checks whether a given interface is supported by the servant.
     * 
     * @param string $interface
     * @return bool
     */
    public function supports(string $interface): bool
    {
        return array_key_exists($interface, $this->interfaces);
    }

    /**
     * removes the service from the servant for the given interface. Must not lock the interface
     * as not set when the servant is a fork.
     * 
     * @param string $interface
     * @return void
     */
    public function forget(string $interface): void
    {
        unset($this->interfaces[$interface]);
    }

    /**
     * removes the service key from the servant for the given interface. Must not lock the interface key
     * as not set when the servant is a fork.
     * 
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->services[$key]);
    }

    /**
     * drops the object instance of the given interface. Must not lock the interface as not set when
     * the servant is a fork.
     * 
     * @param string $key
     * @return void
     */
    public function dropInstanceOfInterface(string $interface): void
    {
        if ( $this->supports($interface) && $this->instances->contains($this->interfaces[$interface]) ) {
            $this->instances->detach($this->interfaces[$interface]);
        }
    }

    /**
     * drops the object instance of the given key. Must not lock the interface as not set when the
     * servant is a fork.
     */
    public function dropInstanceOfService(string $key): void
    {
        if ( $this->has($key) && $this->instances->contains($this->services[$key]) ) {
            $this->instances->detach($this->services[$key]);
        }
    }

    /**
     * forks the servant and makes all interfaces of the forked servant available to the fork.
     * The fork can overwrite services without touching the forked servant.
     * 
     * @return ServantInterface
     */
    public function fork(): ServantInterface
    {
        return new Servant($this);
    }

    /**
     * checks whether this Servant is a fork or not.
     * 
     * @return bool
     */
    public function isFork(): bool
    {
        return $this->origin instanceof ServantInterface;
    }

    /**
     * calls the provided callback.
     * 
     * @param callable $callback
     * @param array $params
     * @return void|mixed
     */
    public function call(callable $callback, array $params)
    {
        $reflectionParameters = $this->cache->dispenseByCallable($callback)->getParameters();
        return $this->call($callback, ... $this->marshalParameters($params, ... $reflectionParameters));
    }

    /**
     * returns the reflection cache instance.
     * 
     * @return null|ReflectionCacheInterface
     */
    public function getReflectionCache(): ? ReflectionCacheInterface
    {
        return $this->cache;
    }

    /**
     * builds the object for th given service instance using the provided parameters.
     * 
     * @param ServiceInterface $service
     * @param array $params
     * @return object
     */
    protected function build(ServiceInterface $service, array $params = []): object
    {
        $object = null;

        if ( $service->hasFactory() ) {
            $object = $this->call($service->getFactory(), $params);
        }
        else {
            $class = $service->getConcrete();
            $constructorParameters = $this->cache->dispenseByClassName($class)->getParameters();
            $object = new $class(... $this->marshalParameters($params, ... $constructorParameters));
        }

        if ( $service->hasInitializer() ) {
            $this->call($service->getInitializer(), [$object]);
        }

        return $object;
    }

    /**
     * marshals the parameter according to the given reflection parameters.
     * 
     * @param array $inboundParameters named or index parameters
     * @param ReflectionParameter[] $reflectionParameters
     * @return Generator
     */
    protected function marshalParameters(array $inboundParameters, ReflectionParameter ... $reflectionParameters): Generator
    {
        foreach ( $reflectionParameters as $currentParameter ) {
            if ( array_key_exists($currentParameter->getPosition(), $inboundParameters) ) {
                yield $currentParameter->getPosition() => $inboundParameters[$position];
                continue;
            }

            if ( array_key_exists($currentParameter->getName(), $inboundParameters) ) {
                yield $currentParameter->getPosition() => $inboundParameters[$currentParameter->getName()];
                continue;
            }

            $classReflection = $currentParameter->getClass();

            if ( $classReflection && ! $currentParameter->isOptional() ) {
                yield $currentParameter->getPosition() => $this->make($classReflection->getName());
            }
        }
    }

}
