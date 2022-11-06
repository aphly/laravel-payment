<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\PaymentMethod;
use Aphly\LaravelPayment\Models\PaymentMethodParams;
use Illuminate\Http\Request;

class ParamsController extends Controller
{

    public $index_url = '/payment_admin/params/index';

    public function index(Request $request){
        $res['method'] = (new PaymentMethod)->getInfo($request);
        $res['search']['title'] = $title = $request->query('title', false);
        $res['search']['string'] = http_build_query($request->query());
        $res['list'] = PaymentMethodParams::where('method_id',$res['method']->id)
            ->Paginate(config('admin.perPage'))->withQueryString();
        return $this->makeView('laravel-payment::admin.params.index', ['res' => $res]);
    }

    public function form(Request $request)
    {
        $res['method'] = (new PaymentMethod)->getInfo($request);
        $res['info'] = PaymentMethodParams::where('id',$request->query('id',0))->firstOrNew();
        return $this->makeView('laravel-payment::admin.params.form',['res'=>$res]);
    }

    public function save(Request $request){
        $res['method'] = (new PaymentMethod)->getInfo($request);
        $request->merge(['method_id'=>$res['method']->id]);
        PaymentMethodParams::updateOrCreate(['id'=>$request->query('id',0)],$request->all());
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url.'?method_id='.$res['method']->id]]);
    }

    public function del(Request $request)
    {
        $res['method'] = (new PaymentMethod)->getInfo($request);
        $this->index_url = $this->index_url.'?method_id='.$res['method']->id;
        $query = $request->query();
        $redirect = $query?$this->index_url.'?'.http_build_query($query):$this->index_url;
        $post = $request->input('delete');
        if(!empty($post)){
            PaymentMethodParams::whereIn('id',$post)->delete();
            throw new ApiException(['code'=>0,'msg'=>'操作成功','data'=>['redirect'=>$redirect]]);
        }
    }

}
