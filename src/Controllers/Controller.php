<?php

namespace Aphly\LaravelPayment\Controllers;

use Illuminate\Support\Facades\View;

class Controller extends \Aphly\Laravel\Controllers\Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            //View::share("res",['title'=>'xxx']);
            return $next($request);
        });
        parent::__construct();
    }
}
