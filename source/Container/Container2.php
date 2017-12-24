<?php
/**
 * Created by PhpStorm.
 * User: kurisu
 * Date: 2017/12/23
 * Time: 19:52
 */

namespace App\Http\Controllers;


//设计容器类，容器类装实例或提供实例的回调函数
class Container
{
    //用于装提供实例的回调函数，真正的函数还会装实例等其他内容
    //从而实现单例等高级功能
    protected $bindings/*绑定，捆绑*/ = [];

    //绑定接口和生成相应实例的回调函数
    public function bind( $abstract/*抽象*/ , $concrete/*具体*/ = null , $shared/*共享*/ = false )
    {
        if (!$concrete instanceof \Closure)/*判断传入函数是否属于这个回调函数类*/
        {
            // 如果提供的参数不是回调函数，则产生默认的回调函数
            $concrete = $this->getClosure/*获取回调*/( $abstract , $concrete );
        }
        $this->bindings[$abstract] = compact( 'concrete' , 'shared' );
    }

    // 默认生成实例的回调函数
    public function getClosure( $abstract , $concrete )
    {
        //生成实例的回调函数，$c 一般为 IoC 容器对象，在调用回调生成实例时提供
        //即 build 函数中的 $concrete ($this)
        return function( $c ) use ( $abstract , $concrete )
        {
            $method = ($abstract == $concrete) ? 'build' : 'make';
            return $c->$method( $concrete );
        };
    }

    //生成实例对象，首先解决接口要实例化类之间的依赖关系
    public function make( $abstract )
    {
        $concrete = $this->getConcrete( $abstract );
        if ($this->isBuildable( $concrete , $abstract ))
        {
            $object = $this->build( $concrete );
        }
        else
        {
            $object = $this->make( $concrete );
        }
        return $object;
    }

    protected function isBuildable( $concrete , $abstract )
    {
        return $concrete === $abstract || $concrete instanceof \Closure;
    }

    // 获取绑定的回调函数
    protected function getConcrete( $abstract )
    {
        if (!isset( $this->bindings[$abstract] ))
        {
            return $abstract;
        }
        return $this->bindings[$abstract]['concrete'];
    }

    //实例化对象
    public function build( $concrete )
    {
        if ($concrete instanceof \Closure)
        {
            return $concrete( $this );
        }
        $reflector = new \ReflectionClass( $concrete );
        if (!$reflector->isInstantiable())
        {
            echo $message = "Target [$concrete] is not instantiable.";
        }
        $constructor = $reflector->getConstructor();
        if (is_null( $constructor ))
        {
            return new $concrete;
        }
        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies( $dependencies );
        return $reflector->newInstanceArgs( $instances );
    }

    //解决通过反射机制实例化对象时的依赖
    protected function getDependencies($parameters)
    {
        $dependencies = [];
        foreach($parameters as $parameter)
        {
            $dependency = $parameter->getClass;
            if (is_null( $dependency ))
            {
                $dependencies[] = null;
            }
            else
            {
                $dependencies[] = $this->resolveClass( $parameter );
            }
        }
        return (array)$dependencies;
    }

    protected function resolveClass(\ReflectionParameter $parameter)
    {
        return $this->make( $parameter->getClass()->name );
    }
}

class Traveller
{
    protected $trafficTool;
    public function __construct( $trafficTool)
    {
        $this->trafficTool = $trafficTool;
    }

    public function visitTibet()
    {
        $this->trafficTool->go();
    }
}

//实例化IoC容器
$app = new Container();
//完成容器的填充
$app->bind( "Visit" , "Train" );
$app->bind( "traveller" , "Traveller" );
//通过容器实现依赖注入，完成类的实例化
$tra = $app->make( "traveller" );
$tra->visitTibet();
