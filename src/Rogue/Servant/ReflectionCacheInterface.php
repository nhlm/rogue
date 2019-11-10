<?php declare(strict_types=1);
/**
 * This file is part of the Rogue PHP Universe.
 * 
 * (c) 2019 Matthias Kaschubowski
 * 
 * @package rogue.servant
 */
namespace Rogue\Servant;

use ReflectionFunctionAbstract;

/**
 * Reflection Cache Interface
 * 
 * @author Matthias Kaschubowski <nihylum@gmail.com>
 * @api 1.0
 */
interface ReflectionCacheInterface {

    /**
     * dispenses the method reflection for the given callable. This method must compose the closure
     * of the given callback.
     * 
     * @param callable $callback
     * @return ReflectionFunctionAbstract
     */
    public function dispenseByCallable(callable $callback): ReflectionFunctionAbstract;

    /**
     * dispenses the method reflection for the given interface constructor, if any.
     * 
     * @param string $interface
     * @return null|ReflectionFunctionAbstract
     */
    public function dispenseByClassName(string $interface): ? ReflectionFunctionAbstract;

    /**
     * dispenses the class method reflection for the given interface and method.
     * 
     * @param string $interface
     * @param string $method
     * @return ReflectionFunctionAbstract
     */
    public function dispenseByClassAndMethod(string $interface, string $method): ReflectionFunctionAbstract;

}
