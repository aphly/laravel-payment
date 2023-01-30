<div class="top-bar">
    <h5 class="nav-title">payment</h5>
</div>
<style>

</style>
<div class="imain">
    <div class="itop ">
        <form method="get" action="/payment_admin/payment/index" class="select_form">
        <div class="search_box ">
            <input type="search" name="id" placeholder="id" autocomplete="false" value="{{$res['search']['id']}}">
            <select name="method_id" >
                <option value="0">All</option>
                @foreach($res['method'] as $val)
                <option value="{{$val->id}}">{{$val->name}}</option>
                @endforeach
            </select>
            <input type="search" name="transaction_id" placeholder="transaction_id" autocomplete="false" value="{{$res['search']['transaction_id']}}">
            <button class="" type="submit">搜索</button>
        </div>
        </form>
    </div>

    <form method="post"  @if($res['search']['string']) action="/payment_admin/payment/del?{{$res['search']['string']}}" @else action="/payment_admin/payment/del" @endif  class="del_form">
    @csrf
        <div class="table_scroll">
            <div class="table">
                <ul class="table_header">
                    <li >ID</li>
                    <li >payment method</li>
                    <li >transaction_id</li>
                    <li >amount</li>
                    <li >状态</li>
                    <li >操作</li>
                </ul>
                @if($res['list']->total())
                    @foreach($res['list'] as $v)
                    <ul class="table_tbody">
                        <li><input type="checkbox" class="delete_box" name="delete[]" value="{{$v['id']}}">{{$v['id']}}</li>
                        <li>{{ $res['method'][$v['method_id']]['name'] }}</li>
                        <li>
                            {{$v['transaction_id']}}
                        </li>
                        <li>
                            {{$v['currency_code']}} {{$v['amount']}}
                        </li>
                        <li>
                            @if($dict['payment_status'])
                                @if($v->status==2)
                                    <span class="badge badge-success">{{$dict['payment_status'][$v->status]}}</span>
                                @else
                                    <span class="badge badge-secondary">{{$dict['payment_status'][$v->status]}}</span>
                                @endif
                            @endif
                        </li>
                        <li>
                            <a class="badge badge-info ajax_get" data-href="/payment_admin/payment/form?id={{$v['id']}}">编辑</a>
                            <a class="badge badge-info ajax_get" data-href="/payment_admin/payment/refund?id={{$v['id']}}">退款</a>
                            <a class="badge badge-info ajax_get" data-href="/payment_admin/payment/show?id={{$v['id']}}">支付平台数据</a>
                        </li>
                    </ul>
                    @endforeach
                    <ul class="table_bottom">
                        <li>
                            <input type="checkbox" class="delete_box deleteboxall"  onclick="checkAll(this)">
                            <button class="badge badge-danger del" type="submit">删除</button>
                        </li>
                        <li >
                            {{$res['list']->links('laravel-admin::common.pagination')}}
                        </li>
                    </ul>
                @endif
            </div>
        </div>

    </form>
</div>


