<?php
/**
 * Created by PhpStorm.
 * User: luoxulx
 * Date: 2019/8/22
 * Time: 下午5:25
 */

namespace App\Http\Controllers\Api;

use League\Fractal\Manager;
use App\Support\Response;
use App\Support\Transform;
use App\Http\Controllers\Controller;


class BaseController extends Controller
{

    protected $response;

    public function __construct()
    {
        $manager = new Manager();

        $this->response = new Response(response(), new Transform($manager));
    }

}
