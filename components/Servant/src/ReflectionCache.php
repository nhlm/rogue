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
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionClass;

/**
 * Reflection Cache Class
 * 
 * @author Matthias Kaschubowski <nihylum@gmail.com>
 * @api 1.0
 */
class ReflectionCache implements ReflectionCacheInterface {

    protected $cache = [];

    /**
     * dispenses the method reflection for the given callable. This method must compose the closure
     * of the given callback.
     * 
     * @param callable $callback
     * @return ReflectionFunctionAbstract
     */
    public function dispenseByCallable(callable $callback): ReflectionFunctionAbstract
    {
        $closure = Closure::fromCallable($callback);
        $key = "closure:".spl_object_hash($closure);

        if ( ! array_key_exists($key, $this->cache) ) {
            $this->cache[$key] = new ReflectionFunction($closure);
        }

        return $this->cache[$key];
    }

    /**
     * dispenses the method reflection for the given interface constructor, if any.
     * 
     * @param string $interface
     * @return null|ReflectionFunctionAbstract
     */
    public function dispenseByClassName(string $interface): ? ReflectionFunctionAbstract
    {
        $key = "class:".$interface."@__construct";

        if ( ! array_key_exists($key, $this->cache) ) {
            $reflection = new ReflectionClass($interface);
            $this->cache[$key] = $reflection->getConstructor();
        }

        return $this->cache[$key];
    }

    /**
     * dispenses the class method reflection for the given interface and method.
     * 
     * @param string $interface
     * @param string $method
     * @return ReflectionFunctionAbstract
     */
    public function dispenseByClassAndMethod(string $interface, string $method): ReflectionFunctionAbstract
    {
        $key = "class:".$interface."@".$method;

        if ( ! array_key_exists($key, $this->cache) ) {
            $reflection = new ReflectionClass($interface);
            $this->cache[$key] = $reflection->getMethod($method);
        }

        return $this->cache[$key];
    }

}
