<?php
/**
 * Created by PhpStorm.
 * User: luoxulx
 * Date: 2019/8/22
 * Time: 下午9:09
 */

namespace App\Http\Middleware;

use Closure;

class CrossHttp
{
    public function handle($request, Closure $next)
    {
        if($request->getMethod() == "OPTIONS") {
            $allowOrigin = [
                'https://live2d.lnmpa.top/'
            ];
            $Origin = $request->header('Origin');
            if(in_array($Origin, $allowOrigin)){
                return response()->json('ok', 200, [
                    # 下面参数视request中header而定
                    'Access-Control-Allow-Origin' => $Origin,
                    'Access-Control-Allow-Headers' => 'x-token',
                    'Access-Control-Allow-Methods' => 'GET,OPTIONS']);
            } else {
                return response()->json('fail', 405);
            }
        }

        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', '*');
        return $response;
    }
}
