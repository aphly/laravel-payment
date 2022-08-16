<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Aphly\LaravelAdmin\Models\Menu;
use Aphly\LaravelAdmin\Models\Role;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public $module_id = 4;

    public function install(){
        $menu = Menu::create(['name' => 'Payment','url' =>'','pid'=>0,'is_leaf'=>0,'module_id'=>$this->module_id,'sort'=>30]);
        if($menu){
            $data=[];
            $data[] =['name' => 'Method','url' =>'/payment_admin/method/index','pid'=>$menu->id,'is_leaf'=>1,'module_id'=>$this->module_id,'sort'=>0];
            $data[] =['name' => 'Payment','url' =>'/payment_admin/payment/index','pid'=>$menu->id,'is_leaf'=>1,'module_id'=>$this->module_id,'sort'=>0];
            DB::table('admin_menu')->insert($data);
        }
        $menuData = Menu::where(['module_id'=>$this->module_id])->get();
        $data=[];
        foreach ($menuData as $val){
            $data[] =['role_id' => Role::MANAGER,'menu_id'=>$val->id];
        }
        DB::table('admin_role_menu')->insert($data);

//        $data=[];
//        DB::table('payment_method')->insert($data);

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
        return 'uninstall_ok';
    }


}
