<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Matomo\Dependencies\DI;

if (!function_exists('Matomo\Dependencies\DI\value')) {
    function value()
    {
        return \DI\value(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\decorate')) {
    function decorate()
    {
        return \DI\decorate(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\create')) {
    function create()
    {
        return \DI\create(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\autowire')) {
    function autowire()
    {
        return \DI\autowire(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\factory')) {
    function factory()
    {
        return \DI\factory(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\get')) {
    function get()
    {
        return \DI\get(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\env')) {
    function env()
    {
        return \DI\env(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\add')) {
    function add()
    {
        return \DI\add(...func_get_args());
    }
}

if (!function_exists('Matomo\Dependencies\DI\string')) {
    function string()
    {
        return \DI\string(...func_get_args());
    }
}

// sometimes used in constructor arguments to classes/DI factory methods, so we want to explicitly alias it before it is encountered
if (!interface_exists('Matomo\Dependencies\DI\FactoryInterface')) {
    class_alias('DI\FactoryInterface', 'Matomo\Dependencies\DI\FactoryInterface');
}
if (!class_exists('Matomo\Dependencies\DI\Container')) {
    class_alias('DI\Container', 'Matomo\Dependencies\DI\Container');
}
