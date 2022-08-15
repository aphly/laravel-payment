<?php

namespace Aphly\LaravelPayment\Controllers\Admin;

use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public $module_id = 4;

    public function install(){
        $data=[];
        $data[] =['id'=>40000,'name' => 'payment','url' =>'','pid'=>0,'is_leaf'=>0,'module_id'=>$this->module_id,'sort'=>30];
        $data[] =['id'=>40001,'name' => 'method','url' =>'/payment_admin/method/index','pid'=>40000,'is_leaf'=>1,'module_id'=>$this->module_id,'sort'=>0];
        $data[] =['id'=>40002,'name' => 'setting','url' =>'/payment_admin/setting/index','pid'=>40000,'is_leaf'=>1,'module_id'=>$this->module_id,'sort'=>0];
        DB::table('admin_menu')->insert($data);

        $data=[];
        for($i=40000;$i<=40002;$i++){
            $data[] =['role_id' => 2,'menu_id'=>$i];
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
        DB::table('payment_setting')->truncate();
        return 'uninstall_ok';
    }


}
