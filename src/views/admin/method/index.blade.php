<div class="top-bar">
    <h5 class="nav-title">method</h5>
</div>
<style>
    .table_scroll .table_header li:nth-child(2),.table_scroll .table_tbody li:nth-child(2){flex: 0 0 300px;}
</style>
<div class="imain">
    <div class="itop ">
        <form method="get" action="/payment_admin/method/index" class="select_form">
        <div class="search_box ">
            <input type="search" name="name" placeholder="method name" value="{{$res['search']['name']}}">
            <button class="" type="submit">搜索</button>
        </div>
        </form>
        <div class="">
            <a class="badge badge-primary ajax_get show_all0_btn" data-href="/payment_admin/method/form">添加</a>
        </div>
    </div>

    <form method="post"  @if($res['search']['string']) action="/payment_admin/method/del?{{$res['search']['string']}}" @else action="/payment_admin/method/del" @endif  class="del_form">
    @csrf
        <div class="table_scroll">
            <div class="table">
                <ul class="table_header">
                    <li >ID</li>
                    <li >method name</li>
                    <li >默认</li>
                    <li >status</li>
                    <li >操作</li>
                </ul>
                @if($res['list']->total())
                    @foreach($res['list'] as $v)
                    <ul class="table_tbody">
                        <li><input type="checkbox" class="delete_box" name="delete[]" value="{{$v['id']}}">{{$v['id']}}</li>
                        <li>{{ $v['name'] }}</li>
                        <li>
                            @if($dict['yes_no'])
                                @if($v->default==1)
                                    <span class="badge badge-success">{{$dict['yes_no'][$v->default]}}</span>
                                @else
                                    <span class="badge badge-secondary">{{$dict['yes_no'][$v->default]}}</span>
                                @endif
                            @endif
                        </li>
                        <li>
                            @if($dict['status'])
                                @if($v['status']==1)
                                    <span class="badge badge-success">{{$dict['status'][$v['status']]}}</span>
                                @else
                                    <span class="badge badge-secondary">{{$dict['status'][$v['status']]}}</span>
                                @endif
                            @endif
                        </li>
                        <li>
                            <a class="badge badge-info ajax_get" data-href="/payment_admin/method/form?id={{$v['id']}}">编辑</a>
                            <a class="badge badge-info ajax_get" data-href="/payment_admin/params/index?method_id={{$v['id']}}">参数设置</a>
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


