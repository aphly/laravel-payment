<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Models\Breadcrumb;
use Aphly\LaravelPayment\Models\PaymentMethod;
use Aphly\LaravelPayment\Models\Payment;
use Aphly\LaravelPayment\Models\PaymentRefund;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public $index_url='/payment_admin/payment/index';

    private $currArr = ['name'=>'支付','key'=>'method'];

    public function index(Request $request)
    {
        $res['search']['id'] = $request->query('id','');
        $res['search']['method_id'] = $request->query('method_id','');
        $res['search']['transaction_id'] = $request->query('transaction_id','');
        $res['search']['string'] = http_build_query($request->query());
        $res['list'] = Payment::when($res['search'],
                function($query,$search) {
                    if($search['id']!==''){
                        $query->where('id',$search['id']);
                    }
                    if($search['method_id']!==''){
                        $query->where('method_id',$search['method_id']);
                    }
                    if($search['transaction_id']!==''){
                        $query->where('transaction_id',$search['transaction_id']);
                    }
                })
            ->orderBy('created_at','desc')
            ->Paginate(config('admin.perPage'))->withQueryString();
        $res['method'] = PaymentMethod::get()->keyBy('id');
        $res['breadcrumb'] = Breadcrumb::render([
            ['name'=>$this->currArr['name'].'管理','href'=>$this->index_url],
        ]);
        return $this->makeView('laravel-payment::admin.payment.index',['res'=>$res]);
    }

    public function form(Request $request)
    {
        $res['info'] = Payment::where('id',$request->query('id',0))->firstOrNew();
        $res['method'] = PaymentMethod::get()->keyBy('id');
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
        return $this->makeView('laravel-payment::admin.payment.form',['res'=>$res]);
    }

    public function save(Request $request){
        Payment::updateOrCreate(['id'=>$request->query('id',0)],$request->all());
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url]]);
    }

    public function show(Request $request)
    {
        $res['info'] = Payment::where('id',$request->query('id',0))->firstOrError();
        $res['transaction_info'] = $res['info']->show($res['info'],true);
        $res['breadcrumb'] = Breadcrumb::render([
            ['name'=>$this->currArr['name'].'管理','href'=>$this->index_url],
            ['name'=>'详情','href'=>'/payment_admin/'.$this->currArr['key'].'/show?id='.$res['info']->id]
        ]);
        return $this->makeView('laravel-payment::admin.payment.show',['res'=>$res]);
    }

    public function refund(Request $request){
        $input = $request->all();
        $res['info'] = Payment::where('id',$input['id'])->firstOrError();
        if($request->isMethod('post')){
            $res['info']->refund($res['info'],$input);
            throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url]]);
        }else{
            $res['paymentRefund'] = PaymentRefund::where('payment_id',$res['info']->id)->get();
            $res['breadcrumb'] = Breadcrumb::render([
                ['name'=>$this->currArr['name'].'管理','href'=>$this->index_url],
                ['name'=>'退款','href'=>'/payment_admin/'.$this->currArr['key'].'/refund?id='.$res['info']->id]
            ]);
            return $this->makeView('laravel-payment::admin.payment.refund',['res'=>$res]);
        }
    }

    public function del(Request $request)
    {
        $query = $request->query();
        $redirect = $query?$this->index_url.'?'.http_build_query($query):$this->index_url;
        $post = $request->input('delete');
        if(!empty($post)){
            Payment::whereIn('id',$post)->delete();
            throw new ApiException(['code'=>0,'msg'=>'操作成功','data'=>['redirect'=>$redirect]]);
        }
    }

}
