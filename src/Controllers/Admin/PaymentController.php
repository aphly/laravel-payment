<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\Method;
use Aphly\LaravelPayment\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public $index_url='/payment_admin/payment/index';

    public function index(Request $request)
    {
        $res['search']['id'] = $id = $request->query('id',false);
        $res['search']['method_id'] = $method_id = $request->query('method_id',false);
        $res['search']['transaction_id'] = $transaction_id = $request->query('transaction_id',false);
        $res['search']['string'] = http_build_query($request->query());
        $res['list'] = Payment::when($id,
                function($query,$id) {
                    return $query->where('id',$id);
                })
            ->when($method_id,
                function($query,$method_id) {
                    return $query->where('method_id',$method_id);
                })
            ->when($transaction_id,
                function($query,$transaction_id) {
                    return $query->where('transaction_id', $transaction_id);
                })
            ->orderBy('id','desc')
            ->Paginate(config('admin.perPage'))->withQueryString();
        $res['method'] = Method::get()->keyBy('id');
        return $this->makeView('laravel-payment::admin.payment.index',['res'=>$res]);
    }

    public function form(Request $request)
    {
        $res['info'] = Payment::where('id',$request->query('id',0))->firstOrNew();
        $res['method'] = Method::get()->keyBy('id');
        return $this->makeView('laravel-payment::admin.payment.form',['res'=>$res]);
    }

    public function save(Request $request){
        Payment::updateOrCreate(['id'=>$request->query('id',0)],$request->all());
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$this->index_url]]);
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
