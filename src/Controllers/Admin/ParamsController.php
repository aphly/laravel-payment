<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Models\Breadcrumb;
use Aphly\LaravelPayment\Models\PaymentMethod;
use Aphly\LaravelPayment\Models\PaymentMethodParams;
use Illuminate\Http\Request;

class ParamsController extends Controller
{

    public $index_url = '/payment_admin/params/index';

    public function index(Request $request){
        $res['method'] = PaymentMethod::where('id',$request->input('method_id',0))->firstOrError();
        $res['search']['title'] = $request->query('title', false);
        $res['search']['string'] = http_build_query($request->query());
        $res['list'] = PaymentMethodParams::where('method_id',$res['method']->id)
            ->Paginate(config('admin.perPage'))->withQueryString();
        $res['breadcrumb'] = Breadcrumb::render([
            ['name'=>'方式管理','href'=>'/payment_admin/method/index'],
            ['name'=>$res['method']->name,'href'=>$this->index_url.'?method_id='.$res['method']->id]
        ]);
        return $this->makeView('laravel-payment::admin.params.index', ['res' => $res]);
    }

    public function form(Request $request)
    {
        $res['method'] = PaymentMethod::where('id',$request->input('method_id',0))->firstOrError();
        $res['info'] = PaymentMethodParams::where('id',$request->query('id',0))->firstOrNew();
        if($res['info']->id){
            $res['breadcrumb'] = Breadcrumb::render([
                ['name'=>'方式管理','href'=>'/payment_admin/method/index'],
                ['name'=>$res['method']->name,'href'=>$this->index_url.'?method_id='.$res['method']->id],
                ['name'=>'编辑','href'=>'/payment_admin/params/form?method_id='.$res['method']->id.'&id='.$res['info']->id]
            ]);
        }else{
            $res['breadcrumb'] = Breadcrumb::render([
                ['name'=>'方式管理','href'=>'/payment_admin/method/index'],
                ['name'=>$res['method']->name,'href'=>$this->index_url.'?method_id='.$res['method']->id],
                ['name'=>'新增','href'=>'/payment_admin/params/form?method_id='.$res['method']->id]
            ]);
        }
        return $this->makeView('laravel-payment::admin.params.form',['res'=>$res]);
    }

    public function save(Request $request){
        $res['method'] = PaymentMethod::where('id',$request->input('method_id',0))->firstOrError();
        $request->merge(['method_id'=>$res['method']->id]);
        PaymentMethodParams::updateOrCreate(['id'=>$request->query('id',0)],$request->all());
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url.'?method_id='.$res['method']->id]]);
    }

    public function del(Request $request)
    {
        $res['method'] = PaymentMethod::where('id',$request->input('method_id',0))->firstOrError();
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
