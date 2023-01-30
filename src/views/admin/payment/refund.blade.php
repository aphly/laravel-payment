
<div class="top-bar">
    <h5 class="nav-title">refund</h5>
</div>
<style>
    .payment_refund li{display: flex;flex-wrap: wrap;margin-bottom: 5px;line-height: 30px;}
    .payment_refund li span:first-child{min-width: 200px;}
    .payment_refund_list li{display: flex;line-height: 30px;}
    .payment_refund_list li span{flex:1;}
    .info_title{font-weight: 600;font-size: 16px;padding-left: 0px;line-height: 40px; }
    .info_title_x{padding-left: 5px;}
</style>
<div class="imain">
    <div>
        <div class="info_title">Payment info</div>
        <ul class="payment_refund info_title_x">
            <li><span>Payment id </span><span>{{$res['info']->id}}</span></li>
            <li><span>Payment method_name</span><span>{{$res['info']->method_name}}</span></li>
            <li><span>Payment transaction_id</span><span>{{$res['info']->transaction_id}}</span></li>
            <li><span>Payment cred_id</span><span>{{$res['info']->cred_id}}</span></li>
            <li><span>Payment notify_func</span><span>{{$res['info']->notify_func}}</span></li>
            <li><span>Payment success_url</span><span>{{$res['info']->success_url}}</span></li>
            <li><span>Payment fail_url</span><span>{{$res['info']->fail_url}}</span></li>
            <li><span>Payment cancel_url</span><span>{{$res['info']->cancel_url}}</span></li>
            <li><span>Payment status</span><span>
                    @if(isset($dict['payment_status']))
                        {{$dict['payment_status'][$res['info']->status]}}
                    @endif
                </span></li>
            <li><span>Payment amount</span><span>{{$res['info']->amount}}</span></li>
            <li><span>Payment currency_code</span><span>{{$res['info']->currency_code}}</span></li>
            <li><span>Payment created_at</span><span>{{$res['info']->created_at}}</span></li>
            <li><span>Payment updated_at</span><span>{{$res['info']->updated_at}}</span></li>
        </ul>
    </div>

    <div>
        <div class="info_title">refund list</div>
        <ul class="payment_refund_list info_title_x">
            <li>
                <span>id</span>
                <span>amount</span>
                <span>status</span>
                <span>cred_id</span>
                <span>cred_status</span>
                <span>created_at</span>
            </li>
            @foreach($res['paymentRefund'] as $val)
            <li>
                <span>{{$val->id}}</span>
                <span>{{$val->amount}}</span>
                <span>
                    @if(isset($dict['payment_refund_status']))
                        {{$dict['payment_refund_status'][$val->status]}}
                    @endif
                </span>
                <span>{{$val->cred_id}}</span>
                <span>{{$val->cred_status}}</span>
                <span>{{$val->created_at}}</span>
            </li>
            @endforeach
        </ul>
    </div>
    <form method="post" @if($res['info']->id) action="/payment_admin/payment/refund?id={{$res['info']->id}}" @else action="/payment_admin/payment/refund" @endif class="save_form">
        @csrf
        <input type="hidden" name="payment_id" class="form-control " value="{{$res['info']->id}}">
        <div class="info_title">refund form</div>
        <div class="info_title_x">
            <div class="form-info">
                <label for="">amount</label>
                <input type="text" name="amount" class="form-control " value="0">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-info">
                <label for="">reason</label>
                <input type="text" name="reason" class="form-control " value="">
                <div class="invalid-feedback"></div>
            </div>
            <br>
            <button class="btn btn-primary" type="submit">保存</button>
        </div>
    </form>

</div>
<style>

</style>
<script>

</script>
