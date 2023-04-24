<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Models\Breadcrumb;
use Aphly\LaravelPayment\Models\PaymentRefund;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public $index_url='/payment_admin/refund/index';

    private $currArr = ['name'=>'退款','key'=>'method'];

    public function index(Request $request)
    {
        $res['search']['name'] = $request->query('name',false);
        $res['search']['string'] = http_build_query($request->query());
        $res['list'] = PaymentRefund::when($res['search']['name'],
                function($query,$name) {
                    return $query->where('name', 'like', '%'.$name.'%');
                })
            ->orderBy('id','desc')
            ->Paginate(config('admin.perPage'))->withQueryString();
        $res['breadcrumb'] = Breadcrumb::render([
            ['name'=>$this->currArr['name'].'管理','href'=>$this->index_url],
        ]);
        return $this->makeView('laravel-payment::admin.refund.index',['res'=>$res]);
    }

    public function form(Request $request)
    {
        $res['info'] = PaymentRefund::where('id',$request->query('id',0))->firstOrNew();
        if($res['info']->id){
            $res['breadcrumb'] = Breadcrumb::render([
                ['name'=>$this->currArr['name'].'管理','href'=>$this->index_url],
                ['name'=>'编辑','href'=>'/payment_admin/'.$this->currArr['key'].'/form?id='.$res['info']->id]
            ]);
        }else{
            $res['breadcrumb'] = Breadcrumb::render([
                ['name'=>$this->currArr['name'].'管理','href'=>$this->index_url],
                ['name'=>'新增','href'=>'/payment_admin/'.$this->currArr['key'].'/form']
            ]);
        }
        return $this->makeView('laravel-payment::admin.refund.form',['res'=>$res]);
    }

    public function save(Request $request){
        $input = $request->all();
        PaymentRefund::updateOrCreate(['id'=>$request->query('id',0)],$input);
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url]]);
    }

    public function del(Request $request)
    {
        $query = $request->query();
        $redirect = $query?$this->index_url.'?'.http_build_query($query):$this->index_url;
        $post = $request->input('delete');
        if(!empty($post)){
            PaymentRefund::whereIn('id',$post)->delete();
            throw new ApiException(['code'=>0,'msg'=>'操作成功','data'=>['redirect'=>$redirect]]);
        }
    }

}
