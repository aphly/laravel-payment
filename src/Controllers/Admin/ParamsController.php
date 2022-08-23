<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\Method;
use Aphly\LaravelPayment\Models\MethodParams;
use Illuminate\Http\Request;

class ParamsController extends Controller
{

    public $index_url = '/payment_admin/params/index';

    public function index(Request $request){
        $res['method'] = (new Method)->getInfo($request);
        $res['filter']['title'] = $title = $request->query('title', false);
        $res['filter']['string'] = http_build_query($request->query());
        $res['list'] = MethodParams::where('method_id',$res['method']->id)
            ->Paginate(config('admin.perPage'))->withQueryString();
        return $this->makeView('laravel-payment::admin.params.index', ['res' => $res]);
    }

    public function form(Request $request)
    {
        $res['method'] = (new Method)->getInfo($request);
        $res['info'] = MethodParams::where('id',$request->query('id',0))->firstOrNew();
        return $this->makeView('laravel-payment::admin.params.form',['res'=>$res]);
    }

    public function save(Request $request){
        $res['method'] = (new Method)->getInfo($request);
        $request->merge(['method_id'=>$res['method']->id]);
        MethodParams::updateOrCreate(['id'=>$request->query('id',0)],$request->all());
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url.'?method_id='.$res['method']->id]]);
    }

    public function del(Request $request)
    {
        $res['method'] = (new Method)->getInfo($request);
        $this->index_url = $this->index_url.'?method_id='.$res['method']->id;
        $query = $request->query();
        $redirect = $query?$this->index_url.'?'.http_build_query($query):$this->index_url;
        $post = $request->input('delete');
        if(!empty($post)){
            MethodParams::whereIn('id',$post)->delete();
            throw new ApiException(['code'=>0,'msg'=>'操作成功','data'=>['redirect'=>$redirect]]);
        }
    }

}
