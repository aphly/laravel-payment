<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\LaravelAdmin\Models\Dict;
use Aphly\LaravelAdmin\Models\Menu;
use Aphly\LaravelAdmin\Models\Module;
use Aphly\LaravelAdmin\Models\Role;
use Aphly\LaravelPayment\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public $module_id = 0;

    public function __construct()
    {
        parent::__construct();
        $module = Module::where('key','payment')->first();
        if(!empty($module)){
            $this->module_id = $module->id;
        }
    }

    public function install(){
        $menu = Menu::create(['name' => '支付中心','url' =>'','pid'=>0,'is_leaf'=>0,'module_id'=>$this->module_id,'sort'=>10]);
        if($menu->id){
            $data=[];
            $data[] =['name' => '支付方式','url' =>'/payment_admin/method/index','pid'=>$menu->id,'is_leaf'=>1,'module_id'=>$this->module_id,'sort'=>0];
            $data[] =['name' => '流水号','url' =>'/payment_admin/payment/index','pid'=>$menu->id,'is_leaf'=>1,'module_id'=>$this->module_id,'sort'=>0];
            DB::table('admin_menu')->insert($data);
        }
        $menuData = Menu::where(['module_id'=>$this->module_id])->get();
        $data=[];
        foreach ($menuData as $val){
            $data[] =['role_id' => Role::MANAGER,'menu_id'=>$val->id];
        }
        DB::table('admin_role_menu')->insert($data);

        $method = PaymentMethod::create(['name' => 'paypal','status'=>1]);
        if($method->id){
            $data=[];
            $data[] =['method_id' => $method->id,'key'=>'environment','val'=>''];
            $data[] =['method_id' => $method->id,'key'=>'client_id','val'=>'AeUNXihK0N-R7lFPTp8hQ3e-v2lpnfYQfct2jRPb-25P6B2-NNS-xhbFDkFkfbJbDUJqfM7WoB5syu5-'];
            $data[] =['method_id' => $method->id,'key'=>'secret','val'=>'EMP-lHKO5g1R-2nxmzhmc5sw_cDhyoCPgjIC45nKY1P-viR9hRzN37DpKallBOCTfakKI8jwffBIZVIW'];
            DB::table('payment_method_params')->insert($data);
        }

        $method = PaymentMethod::create(['name' => 'stripe','status'=>1]);
        if($method->id){
            $data=[];
            $data[] =['method_id' => $method->id,'key'=>'pk','val'=>'pk_test_51Lev4CB2u33uLmOKX6Wn0dUevviRypd7bb1vTwH4q9AcCjT9yxFGVBMLWQrKrL7qA0DNoHrfKzL2w4Qmvp0I9LqJ00MGHJAEJ7'];
            $data[] =['method_id' => $method->id,'key'=>'sk','val'=>'sk_test_51Lev4CB2u33uLmOKI6ESGWaTfKiT4zOPZYRDe2yMizTozDQH6tpkuDxmf8uAV21vURIjUOngEnQQdXmSIvrWzb0j003d3rF8IL'];
            $data[] =['method_id' => $method->id,'key'=>'es','val'=>'whsec_q20KcAiMdAUXZE4xJPIhqGCLcbDMhDbq'];
            DB::table('payment_method_params')->insert($data);
        }

        $dict = Dict::create(['name' => '支付状态','key'=>'payment_status','module_id'=>$this->module_id]);
        if($dict->id){
            $data=[];
            $data[] =['dict_id' => $dict->id,'name'=>'未支付','value'=>'1','fixed'=>'0'];
            $data[] =['dict_id' => $dict->id,'name'=>'已支付','value'=>'2','fixed'=>'0'];
            DB::table('admin_dict_value')->insert($data);
        }
        return 'install_ok';
    }
    public function uninstall(){
        $admin_menu = DB::table('admin_menu')->where('module_id',$this->module_id);
        $arr = $admin_menu->get()->toArray();
        if($arr){
            $admin_menu->delete();
            $ids = array_column($arr,'id');
            DB::table('admin_role_menu')->whereIn('menu_id',$ids)->delete();
        }

        $admin_dict = DB::table('admin_dict')->where('module_id',$this->module_id);
        $arr = $admin_dict->get()->toArray();
        if($arr){
            $admin_dict->delete();
            $ids = array_column($arr,'id');
            DB::table('admin_dict_value')->whereIn('dict_id',$ids)->delete();
        }
        DB::table('payment_method')->truncate();
        DB::table('payment_method_params')->truncate();
        return 'uninstall_ok';
    }


}
