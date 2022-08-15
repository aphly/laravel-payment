<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\Method;
use Aphly\LaravelPayment\Models\MethodParams;
use Illuminate\Http\Request;

class MethodController extends Controller
{
    public $index_url='/payment_admin/method/index';

    public function index(Request $request)
    {
        $res['filter']['name'] = $name = $request->query('name',false);
        $res['filter']['string'] = http_build_query($request->query());
        $res['list'] = Method::when($name,
                function($query,$name) {
                    return $query->where('name', 'like', '%'.$name.'%');
                })
            ->orderBy('id','desc')
            ->Paginate(config('admin.perPage'))->withQueryString();
        return $this->makeView('laravel-payment::admin.method.index',['res'=>$res]);
    }

    public function form(Request $request)
    {
        $res['info'] = Method::where('id',$request->query('id',0))->firstOrNew();
        return $this->makeView('laravel-payment::admin.method.form',['res'=>$res]);
    }

    public function save(Request $request){
        Method::updateOrCreate(['id'=>$request->query('id',0)],$request->all());
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url]]);
    }

    public function del(Request $request)
    {
        $query = $request->query();
        $redirect = $query?$this->index_url.'?'.http_build_query($query):$this->index_url;
        $post = $request->input('delete');
        if(!empty($post)){
            Method::whereIn('id',$post)->delete();
            throw new ApiException(['code'=>0,'msg'=>'操作成功','data'=>['redirect'=>$redirect]]);
        }
    }

    public $params_url='/payment_admin/method_params/index';

    public function paramsIndex(Request $request)
    {
        $res['method_id'] = $request->query('method_id');
        $res['method_info'] = Method::where('id',$res['method_id'])->first();
        $res['list'] = MethodParams::where('method_id', $res['method_id'])
            ->orderBy('id','desc')
            ->Paginate(config('admin.perPage'))->withQueryString();
        return $this->makeView('laravel-payment::admin.method.params_index',['res'=>$res]);
    }

    public function paramsForm(Request $request)
    {
        $res['method_id'] = $request->query('method_id');
        $res['method_info'] = Method::where('id',$res['method_id'])->first();
        $res['info'] = MethodParams::where('id',$request->query('id',0))->firstOrNew();
        return $this->makeView('laravel-payment::admin.method.params_form',['res'=>$res]);
    }

    public function paramsSave(Request $request){
        $input = $request->all();
        $input['method_id'] = $request->query('method_id');
        MethodParams::updateOrCreate(['id'=>$request->query('id',0)],$input);
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->params_url.'?method_id='.$input['method_id']]]);
    }

    public function paramsDel(Request $request)
    {
        $query = $request->query();
        $redirect = $query?$this->params_url.'?'.http_build_query($query):$this->params_url;
        $post = $request->input('delete');
        if(!empty($post)){
            MethodParams::whereIn('id',$post)->delete();
            throw new ApiException(['code'=>0,'msg'=>'操作成功','data'=>['redirect'=>$redirect]]);
        }
    }

}
