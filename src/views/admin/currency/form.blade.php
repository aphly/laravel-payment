
<div class="top-bar">
    <h5 class="nav-title">{!! $res['breadcrumb'] !!}</h5>
</div>
<div class="imain">
    <form method="post" @if($res['info']->id) action="/payment_admin/currency/save?id={{$res['info']->id}}" @else action="/payment_admin/currency/save" @endif class="save_form">
        @csrf
        <div class="">
            <div class="form-group">
                <label >名称</label>
                <input type="text" name="name" class="form-control " value="{{$res['info']->name}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label >中文名称</label>
                <input type="text" name="cn_name" class="form-control " value="{{$res['info']->cn_name}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label >时区</label>
                <select name="timezone" class="form-control">
                    <option value="" >空</option>
                    @foreach($res['timezone_identifiers'] as $val)
                        <option value="{{$val}}" @if($res['info']->timezone==$val) selected @endif>{{$val}}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label >code</label>
                <input type="text" name="code" class="form-control " value="{{$res['info']->code}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label >symbol_left</label>
                <input type="text" name="symbol_left" class="form-control " value="{{$res['info']->symbol_left}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label >symbol_right</label>
                <input type="text" name="symbol_right" class="form-control " value="{{$res['info']->symbol_right}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label >decimal_place</label>
                <input type="text" name="decimal_place" class="form-control " value="{{$res['info']->decimal_place}}">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-group">
                <label >value</label>
                <input type="text" name="value" class="form-control " value="{{$res['info']->value}}">
                <div class="invalid-feedback"></div>
            </div>

            <div class="form-group">
                <label >默认</label>
                <select name="default" class="form-control">
                    @if(isset($dict['yes_no']))
                        @foreach($dict['yes_no'] as $key=>$val)
                            <option value="{{$key}}" @if($res['info']->default===$key ) selected @endif>{{$val}}</option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="form-group">
                <label >状态</label>
                <select name="status" class="form-control">
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

