
<div class="top-bar">
    <h5 class="nav-title">info</h5>
</div>
<div class="imain">
    <div class="">
            <div class="form-group">
                <label for="">id</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->id??0}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">method</label>
                <input type="text" readonly class="form-control " value="{{$res['method'][$res['info']->method_id]['name']}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">transaction_id</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->transaction_id??0}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">cred_id</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->cred_id??0}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">notify_func</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->notify_func??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">success_url</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->success_url??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">fail_url</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->fail_url??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">cancel_url</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->cancel_url??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">notify_type</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->notify_type??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">amount</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->amount??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">currency_code</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->currency_code??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">??????</label>
                <select readonly class="form-control">
                    @if(isset($dict['payment_status']))
                        @foreach($dict['payment_status'] as $key=>$val)
                            <option value="{{$key}}" @if($res['info']->status==$key) selected @endif>{{$val}}</option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">created_at</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->created_at??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">updated_at</label>
                <input type="text" readonly class="form-control " value="{{$res['info']->updated_at??''}}">
                <div class="invalid-feedback"></div>
            </div>
            <button class="btn btn-primary" type="submit">??????</button>
        </div>
</div>
<style>

</style>
<script>

</script>
