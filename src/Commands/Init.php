<?php

namespace Aphly\LaravelPayment\Commands;

use Aphly\Laravel\Models\CommonDict;
use Aphly\Laravel\Models\CommonDictValue;
use Aphly\LaravelAdmin\Models\AdminManager;
use Aphly\LaravelAdmin\Models\AdminMenu;
use Aphly\LaravelPayment\Models\PaymentMethod;
use Aphly\LaravelPayment\Models\PaymentMethodParams;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-payment:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    protected $module = 'laravel-payment';

    public function clear()
    {
        AdminMenu::where('module',$this->module)->delete();
        CommonDict::where('module',$this->module)->delete();
        CommonDictValue::where('module',$this->module)->delete();
        PaymentMethod::truncate();
        PaymentMethodParams::truncate();
    }

    public function handle()
    {
        $this->clear();

        $manager = AdminManager::where('username','admin')->firstOrError();
        $menu = AdminMenu::create(['name' => '支付中心','route' =>'','pid'=>0,'uid'=>$manager->uid,'type'=>1,'sort'=>10,'module'=>$this->module]);
        if($menu->id){
            $data=[];
            $data[] =['name' => '货币','route' =>'payment_admin/currency/index','pid'=>$menu->id,'uid'=>$manager->uid,'type'=>2,'module'=>$this->module,'sort'=>0];
            $data[] =['name' => '支付方式','route' =>'payment_admin/method/index','pid'=>$menu->id,'uid'=>$manager->uid,'type'=>2,'sort'=>0,'module'=>$this->module];
            $data[] =['name' => '流水号','route' =>'payment_admin/payment/index','pid'=>$menu->id,'uid'=>$manager->uid,'type'=>2,'sort'=>0,'module'=>$this->module];
            DB::table('admin_menu')->insert($data);
        }

        $data=[];
        $data[] =['name' =>"Pound Sterling",'timezone'=>"Europe/London",'code'=>"GBP",'symbol_left'=>"£", 'symbol_right'=>"", 'decimal_place'=>"2", 'value'=>0.8044, 'status'=>1,'default'=>0];
        $data[] =['name' =>"US Dollar",'timezone'=>"America/New_York",'code'=>"USD",'symbol_left'=>"$", 'symbol_right'=>"", 'decimal_place'=>"2", 'value'=>1, 'status'=>1,'default'=>1];
        $data[] =['name' =>"Euro",'timezone'=>"Europe/Berlin",'code'=>"EUR",'symbol_left'=>"€", 'symbol_right'=>"", 'decimal_place'=>"2", 'value'=>0.9362, 'status'=>1,'default'=>0];
        DB::table('payment_currency')->insert($data);

        $method = PaymentMethod::create(['name' => 'paypal','status'=>1,'default'=>1]);
        if($method->id){
            $data=[];
            $data[] =['method_id' => $method->id,'key'=>'environment','val'=>''];
            $data[] =['method_id' => $method->id,'key'=>'client_id','val'=>'AXeCqoXm87DP2phnjdGPvezz9MXESXg9NKq-gOC5zfzS1umTF4KH5p5eJzahBwRCmpPtSs-Qi5hyuwuN'];
            $data[] =['method_id' => $method->id,'key'=>'secret','val'=>'ELRFtOiwsRfGAG_Zs7-6ezrsirRvl702YWmf-Zg424J4mEfwsM2wmW0SVJwCOET5LDSFxG5W3XlaekYS'];
            $data[] =['method_id' => $method->id,'key'=>'webhookId','val'=>'9DB44140G51518449'];
            DB::table('payment_method_params')->insert($data);
        }

        $method = PaymentMethod::create(['name' => 'stripe','status'=>1,'default'=>0]);
        if($method->id){
            $data=[];
            $data[] =['method_id' => $method->id,'key'=>'pk','val'=>'pk_test_51Lev4CB2u33uLmOKX6Wn0dUevviRypd7bb1vTwH4q9AcCjT9yxFGVBMLWQrKrL7qA0DNoHrfKzL2w4Qmvp0I9LqJ00MGHJAEJ7'];
            $data[] =['method_id' => $method->id,'key'=>'sk','val'=>'sk_test_51Lev4CB2u33uLmOKI6ESGWaTfKiT4zOPZYRDe2yMizTozDQH6tpkuDxmf8uAV21vURIjUOngEnQQdXmSIvrWzb0j003d3rF8IL'];
            $data[] =['method_id' => $method->id,'key'=>'es','val'=>'whsec_q20KcAiMdAUXZE4xJPIhqGCLcbDMhDbq'];
            DB::table('payment_method_params')->insert($data);
        }

        $method = PaymentMethod::create(['name' => 'stripeCard','status'=>1,'default'=>0]);
        if($method->id){
            $data=[];
            $data[] =['method_id' => $method->id,'key'=>'pk','val'=>'pk_test_51Lev4CB2u33uLmOKX6Wn0dUevviRypd7bb1vTwH4q9AcCjT9yxFGVBMLWQrKrL7qA0DNoHrfKzL2w4Qmvp0I9LqJ00MGHJAEJ7'];
            $data[] =['method_id' => $method->id,'key'=>'sk','val'=>'sk_test_51Lev4CB2u33uLmOKI6ESGWaTfKiT4zOPZYRDe2yMizTozDQH6tpkuDxmf8uAV21vURIjUOngEnQQdXmSIvrWzb0j003d3rF8IL'];
            $data[] =['method_id' => $method->id,'key'=>'es','val'=>'whsec_oPr9R31JYElXmcDWiUSjbiAGKwOSXfzO'];
            DB::table('payment_method_params')->insert($data);
        }

        $dict = CommonDict::create(['name' => '支付状态','uid'=>$manager->uid,'key'=>'payment_status']);
        if($dict->id){
            $data=[];
            $data[] =['dict_id' => $dict->id,'name'=>'未支付','value'=>'0'];
            $data[] =['dict_id' => $dict->id,'name'=>'已支付','value'=>'1'];
            $data[] =['dict_id' => $dict->id,'name'=>'已批准','value'=>'2'];
            DB::table('common_dict_value')->insert($data);
        }

        $dict = CommonDict::create(['name' => '支付退款状态','uid'=>$manager->uid,'key'=>'payment_refund_status']);
        if($dict->id){
            $data=[];
            $data[] =['dict_id' => $dict->id,'name'=>'等待退款','value'=>'0'];
            $data[] =['dict_id' => $dict->id,'name'=>'退款成功','value'=>'1'];
            DB::table('common_dict_value')->insert($data);
        }
        return 'install_ok';
    }
}
