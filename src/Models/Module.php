<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Models\Dict;
use Aphly\Laravel\Models\Manager;
use Aphly\Laravel\Models\Menu;
use Aphly\Laravel\Models\Module as Module_base;
use Illuminate\Support\Facades\DB;

class Module extends Module_base
{
    public $dir = __DIR__;

    public function install($module_id){
        parent::install($module_id);
        $manager = Manager::where('username','admin')->firstOrError();
        $menu = Menu::create(['name' => '支付中心','route' =>'','pid'=>0,'uuid'=>$manager->uuid,'type'=>1,'module_id'=>$module_id,'sort'=>10]);
        if($menu->id){
            $data=[];
            $data[] =['name' => '支付方式','route' =>'payment_admin/method/index','pid'=>$menu->id,'uuid'=>$manager->uuid,'type'=>2,'module_id'=>$module_id,'sort'=>0];
            $data[] =['name' => '流水号','route' =>'payment_admin/payment/index','pid'=>$menu->id,'uuid'=>$manager->uuid,'type'=>2,'module_id'=>$module_id,'sort'=>0];
            DB::table('admin_menu')->insert($data);
        }
        $menuData = Menu::where(['module_id'=>$module_id])->get();
        $data=[];
        foreach ($menuData as $val){
            $data[] =['role_id' => 1,'menu_id'=>$val->id];
        }
        DB::table('admin_role_menu')->insert($data);

        $method = PaymentMethod::create(['name' => 'paypal','status'=>1,'default'=>1]);
        if($method->id){
            $data=[];
            $data[] =['method_id' => $method->id,'key'=>'environment','val'=>''];
            $data[] =['method_id' => $method->id,'key'=>'client_id','val'=>'AXeCqoXm87DP2phnjdGPvezz9MXESXg9NKq-gOC5zfzS1umTF4KH5p5eJzahBwRCmpPtSs-Qi5hyuwuN'];
            $data[] =['method_id' => $method->id,'key'=>'secret','val'=>'ELRFtOiwsRfGAG_Zs7-6ezrsirRvl702YWmf-Zg424J4mEfwsM2wmW0SVJwCOET5LDSFxG5W3XlaekYS'];
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

        $dict = Dict::create(['name' => '支付状态','uuid'=>$manager->uuid,'key'=>'payment_status','module_id'=>$module_id]);
        if($dict->id){
            $data=[];
            $data[] =['dict_id' => $dict->id,'name'=>'未支付','value'=>'0'];
            $data[] =['dict_id' => $dict->id,'name'=>'已支付','value'=>'1'];
            DB::table('admin_dict_value')->insert($data);
        }

        $dict = Dict::create(['name' => '支付退款状态','uuid'=>$manager->uuid,'key'=>'payment_refund_status','module_id'=>$module_id]);
        if($dict->id){
            $data=[];
            $data[] =['dict_id' => $dict->id,'name'=>'等待退款','value'=>'0'];
            $data[] =['dict_id' => $dict->id,'name'=>'退款成功','value'=>'1'];
            DB::table('admin_dict_value')->insert($data);
        }
        return 'install_ok';
    }

    public function uninstall($module_id){
        parent::uninstall($module_id);
        return 'uninstall_ok';
    }


}
