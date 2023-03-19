<div class="top-bar">
    <h5 class="nav-title">method - {{$res['method']->name}}</h5>
</div>
<style>
    .table_scroll .table_header li:nth-child(2),.table_scroll .table_tbody li:nth-child(2){flex: 0 0 300px;}
</style>
<div class="imain">
    <div class="itop ">
        <div>
            {{$res['method']->name}}
        </div>
        <div class="">
            <a class="badge badge-primary ajax_get show_all0_btn" data-href="/payment_admin/params/form?method_id={{$res['method']->id}}">添加</a>
        </div>
    </div>

    <form method="post"  action="/payment_admin/params/del?method_id={{$res['method']->id}}"  class="del_form">
    @csrf
        <div class="table_scroll">
            <div class="table">
                <ul class="table_header">
                    <li >ID</li>
                    <li >key</li>
                    <li >val</li>
                    <li >操作</li>
                </ul>
                @if($res['list']->total())
                    @foreach($res['list'] as $v)
                    <ul class="table_tbody">
                        <li><input type="checkbox" class="delete_box" name="delete[]" value="{{$v['id']}}">{{$v['id']}}</li>
                        <li>{{ $v['key'] }}</li>
                        <li>
                            {{ $v['val'] }}
                        </li>
                        <li>
                            <a class="badge badge-info ajax_get" data-href="/payment_admin/params/form?method_id={{$res['method']->id}}&id={{$v['id']}}">编辑</a>
                        </li>
                    </ul>
                    @endforeach
                    <ul class="table_bottom">
                        <li>
                            <input type="checkbox" class="delete_box deleteboxall"  onclick="checkAll(this)">
                            <button class="badge badge-danger del" type="submit">删除</button>
                        </li>
                        <li >
                            {{$res['list']->links('laravel::admin.pagination')}}
                        </li>
                    </ul>
                @endif
            </div>
        </div>

    </form>
</div>


