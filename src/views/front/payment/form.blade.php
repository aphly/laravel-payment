

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
                <label for="">price</label>
                <input type="number" name="price" class="form-control " value="10.12">
                <div class="invalid-feedback"></div>
            </div>
            <button class="btn btn-primary" type="submit">send</button>
        </div>
    </form>
</div>
