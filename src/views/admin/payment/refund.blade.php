
<div class="top-bar">
    <h5 class="nav-title">refund</h5>
</div>
<div class="imain">
    <div>
        <ul>
            <li><span>Payment id</span> : <span>{{$res['info']->id}}</span></li>
            <li><span>Payment method_name</span> : <span>{{$res['info']->method_name}}</span></li>
            <li><span>Payment transaction_id</span> : <span>{{$res['info']->transaction_id}}</span></li>
            <li><span>Payment cred_id</span> : <span>{{$res['info']->cred_id}}</span></li>
            <li><span>Payment notify_func</span> : <span>{{$res['info']->notify_func}}</span></li>
            <li><span>Payment success_url</span> : <span>{{$res['info']->success_url}}</span></li>
            <li><span>Payment fail_url</span> : <span>{{$res['info']->fail_url}}</span></li>
            <li><span>Payment cancel_url</span> : <span>{{$res['info']->cancel_url}}</span></li>
            <li><span>Payment status</span> : <span>
                    @if(isset($dict['payment_status']))
                        {{$dict['payment_status'][$res['info']->status]}}
                    @endif
                </span></li>
            <li><span>Payment amount</span> : <span>{{$res['info']->amount}}</span></li>
            <li><span>Payment currency_code</span> : <span>{{$res['info']->currency_code}}</span></li>
            <li><span>Payment created_at</span> : <span>{{$res['info']->created_at}}</span></li>
            <li><span>Payment updated_at</span> : <span>{{$res['info']->updated_at}}</span></li>
        </ul>
    </div>

    <div>
        <br>
        <br>
        <ul>
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
                <span>{{$val->status}}</span>
                <span>{{$val->cred_id}}</span>
                <span>{{$val->cred_status}}</span>
                <span>{{$val->created_at}}</span>
            </li>
            @endforeach
        </ul>
        <br>
        <br>
    </div>
    <form method="post" @if($res['info']->id) action="/payment_admin/payment/refund?id={{$res['info']->id}}" @else action="/payment_admin/payment/refund" @endif class="save_form">
        @csrf
        <input type="hidden" name="payment_id" class="form-control " value="{{$res['info']->id}}">
        <div class="">
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
            <button class="btn btn-primary" type="submit">保存</button>
        </div>
    </form>

</div>
<style>

</style>
<script>

</script>
