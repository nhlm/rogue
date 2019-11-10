<?php declare(strict_types=1);
/**
 * This file is part of the Rogue PHP Universe.
 * 
 * (c) 2019 Matthias Kaschubowski
 * 
 * @package rogue.servant
 */
namespace Rogue\Servant;

use Closure;

/**
 * Service Class
 * 
 * @author Matthias Kaschubowski <nihylum@gmail.com>
 * @api 1.0
 */
class Service implements ServiceInterface {

    /**
     * @var string
     */
    protected $interface;

    /**
     * @var string 
     */
    protected $concrete;

    /**
     * @var callable|null
     */
    protected $factory;

    /**
     * @var callable|null
     */
    protected $initializer;

    /**
     * @var bool
     */
    protected $singleton = false;

    /**
     * @var ReflectionCacheInterface
     */
    protected $reflectionCache;

    /**
     * The constructor.
     * 
     * @param string $interface
     */
    public function __construct(string $interface, ReflectionCacheInterface $reflectionCache)
    {
        $this->interface = $this->concrete = $interface;
        $this->reflectionCache = $reflectionCache;
    }

    /**
     * retruns the interface/class name of the service
     * 
     * @return string
     */
    public function getInterface(): string
    {
        return $this->interface;
    }

    /**
     * retruns the concrete of the interface, if no concrete is set the interface name of the service
     * will be returned.
     * 
     * @return string
     */
    public function getConcrete(): string
    {
        return $this->concrete;
    }

    /**
     * assigns a new classname as a concrete to the service.
     * 
     * @param string $className
     * @throws ServantException
     * @return ServiceInterface
     */
    public function withConcrete(string $className): ServiceInterface
    {
        if ( ! is_a($className, $this->interface, true) ) {
            throw new ServantException(
                'The provided concrete class name `'.$className.'` is not compatible with the service interface'
            );
        }

        $this->concrete = $className;

        return $this;
    }

    /**
     * returns the factory or null if no factory was set.
     * 
     * @return null|callable
     */
    public function getFactory(): ? callable
    {
        return $this->factory;
    }

    /**
     * assigns a factory callable as a concrete to the service. The assigned factory callable must
     * serve a return type that is compatible to the service interface.
     * 
     * @param callable $typedFactory
     * @throws ServantException
     * @return ServiceInterface
     */
    public function withFactory(callable $typedFactory): ServiceInterface
    {
        $reflection = $this->reflectionCache->dispenseByCallable($typedFactory);
        $returnType = $reflection->getReturnType()->getName();

        if ( ! is_a($returnType, $this->interface, true) ) {
            throw new ServantException(
                'The provided factory must set a compatible class for the service `'.
                $this->interface.'` as its return type'
            );
        }

        $this->factory = Closure::fromCallable($typedFactory);
        $this->concrete = $returnType;

        return $this;
    }

    /**
     * checks whether a factory is present or not.
     * 
     * @return bool
     */
    public function hasFactory(): bool
    {
        return is_callable($this->factory);
    }

    /**
     * returns the initializer or null if no initializer is present.
     * 
     * @return null|callable
     */
    public function getInitializer(): ? callable
    {
        return $this->initializer;
    }

    /**
     * assigns a initializer that will be called after the object is constructed. The initilizer will
     * receive the create object as its first argument, all other arguments of the initilizer will be
     * auto-wired.
     * 
     * @param callable $callback
     * @throws ServantException
     * @return ServiceInterface
     */
    public function withInitializer(callable $callback): ServiceInterface
    {
        $reflection = $this->reflectionCache->dispenseByCallable($callback);
        
        if ( ! ( $reflection->getNumberOfParameters() === 0 ) ) {
            throw new ServantException(
                'The provided initializer must have at least one parameter requiring the interface '. 
                'of this service'
            );
        }

        if ( ! is_a($reflection->getParameters()[0]->getClass()->getName(), $this->interface, true) ) {
            throw new ServantException(
                'The provided initializer must require as its first parameter a class that is '. 
                'compatible to the class interface `'.$this->interface.'`'
            );
        }

        $this->initializer = Closure::fromCallback($callback);

        return $this;
    }

    /**
     * checks whether an initializer has been assigned or not.
     * 
     * @return bool
     */
    public function hasInitializer(): bool
    {
        return is_callable($this->initializer);
    }

    /**
     * controls the singleton flag of the service. If set to true, the responsible servant must cache
     * the instance and deliver the cached instance when the service will be wired/fetched in the future.
     * 
     * @param bool $flag
     * @return ServiceInterface
     */
    public function singleton(bool $flag = true): ServiceInterface
    {
        $this->singleton = $flag;

        return $this;
    }

    /**
     * checks whether the resulting instance must be a singleton or not.
     * 
     * @return bool
     */
    public function mustBeSingleton(): bool
    {
        return $this->singleton;
    }

}
