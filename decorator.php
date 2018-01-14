<?php
/**
 * Created by PhpStorm.
 * User: kurisu
 * Date: 2018/01/14
 * Time: 15:54
 */

require_once '../../vendor/autoload.php';

interface Step
{
    public static function go( Closure $next );
}

/**
 * Class FirstStop
 */
class FirstStep implements Step
{
    public static function go( Closure $next ){
        echo "开启session,获取数据<br/>";
        $next();
        echo "保存数据，关闭session<br/>";
    }
}

/**
 * @param $step
 * @param $className
 * @return Closure
 */
function goFun( $step , $className ){
    dump( $step , $className );
    return function() use ( $step , $className ){
        return $className::go( $step );
    };
}


function then(){
    $steps   = ['FirstStep'];
    $prepare = function(){
        echo "请求向路由器传递，返回响应<br>";
    };
    $go      = array_reduce( $steps , "goFun" , $prepare );
    $go();
}

then();