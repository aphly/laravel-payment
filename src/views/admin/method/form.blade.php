
<div class="top-bar">
    <h5 class="nav-title">info</h5>
</div>
<div class="imain">
    <form method="post" @if($res['info']->id) action="/payment_admin/method/save?id={{$res['info']->id}}" @else action="/payment_admin/method/save" @endif class="save_form">
        @csrf
        <div class="">
            <div class="form-info">
                <label for="">名称</label>
                <input type="text" name="name" class="form-control " value="{{$res['info']->name}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-info">
                <label for="">排序</label>
                <input type="number" name="sort" class="form-control " value="{{$res['info']->sort??0}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label for="">状态</label>
                <select name="status"  class="form-control">
                    @if(isset($dict['status']))
                        @foreach($dict['status'] as $key=>$val)
                            <option value="{{$key}}" @if($res['info']->status==$key) selected @endif>{{$val}}</option>
                        @endforeach
                    @endif
                </select>
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
