<?php

namespace Aphly\LaravelPayment\Controllers\Admin;



class Controller extends \Aphly\LaravelAdmin\Controllers\Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            return $next($request);
        });
        parent::__construct();
    }
}
