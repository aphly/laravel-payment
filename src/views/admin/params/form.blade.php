
<div class="top-bar">
    <h5 class="nav-title">info</h5>
</div>
<div class="imain">
    <form method="post" @if($res['info']->id) action="/payment_admin/params/save?method_id={{$res['method']->id}}&id={{$res['info']->id}}" @else action="/payment_admin/params/save?method_id={{$res['method']->id}}" @endif class="save_form">
        @csrf
        <div class="">
            <div class="form-group">
                <label for="">key</label>
                <input type="text" name="key" class="form-control " value="{{$res['info']->key}}">
                <div class="invalid-feedback"></div>
            </div>

            <div class="form-group">
                <label for="">val</label>
                <input type="text" name="val" class="form-control " value="{{$res['info']->val}}">
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
