

<div class="imain">
    <form method="post" action="/payment/order" class="save_form">
        @csrf
        <div class="">
            <div class="form-info">
                <label for="">payment_id</label>
                <input type="text" name="payment_id" class="form-control " value="1">
                <div class="invalid-feedback"></div>
            </div>
            <div class="form-info">
                <label for="">sort</label>
                <input type="number" name="sort" class="form-control " value="10">
                <div class="invalid-feedback"></div>
            </div>
            <button class="btn btn-primary" type="submit">保存</button>
        </div>
    </form>
</div>
