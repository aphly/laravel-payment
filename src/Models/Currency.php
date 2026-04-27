<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Libs\Math;
use Aphly\Laravel\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Currency extends Model
{
    use HasFactory;
    protected $table = 'payment_currency';
    public $timestamps = false;

    protected $fillable = [
        'name','code','symbol_left','symbol_right','decimal_place','value','status','default','cn_name'
    ];

    public function findOneByCode($code) {
        $res = self::where('code', $code)->first();
        return !empty($res)?$res->toArray():[];
    }

    static public function findAll($cache=true) {
        if($cache){
            return Cache::rememberForever('currency', function (){
                return self::where('status',1)->get()->keyBy('id')->toArray();
            });
        }else{
            return self::where('status',1)->get()->keyBy('id')->toArray();
        }
    }

    static public $allDefaultCurr = [];

    static function allDefaultCurr(){
        if(!self::$allDefaultCurr){
            $currency_all = self::findAll();
            $default = [];
            foreach($currency_all as $val){
                if($val['default']==1){
                    $default = $val;
                }
            }
            $currency_id = session('currency_id');
            if($currency_id) {
                self::$allDefaultCurr = [$currency_all,$default,$currency_all[$currency_id]];
            }else{
                self::$allDefaultCurr = [$currency_all,$default,$default];
            }
        }
        return self::$allDefaultCurr;
    }

    static function toDefault($price){
        $price = floatval($price);
        list($currency_all,$default,$info) = self::allDefaultCurr();
        if($currency_all && $default && $info){
            if($info['value']>0 && $default['value']>0){
                if($info['value']!=$default['value']) {
                    //$price = $price * $default['value'] / $info['value'];
                    $price = Math::div(Math::mul($price , $default['value']) , $info['value']);
                }
                return self::numberFormat($price,$default);
            }
        }
        return $price;
    }

    static function format($price,$type = 0){
        $price_us = floatval($price);
        $price = $price_us;
        list($currency_all,$default,$info) = self::allDefaultCurr();
        if($currency_all && $default && $info){
            if($info['value']>0 && $default['value']>0){
                if($info['value']!=$default['value']){
                    //$price = $price*$info['value']/$default['value'];
                    $price = Math::div(Math::mul($price , $info['value']) , $default['value']);
                }
            }
            $price = self::numberFormat($price,$info);
            if($type==1){
                return $price;
            }else{
                $string = self::_format($price,$info);
                if($type==2){
                    return [$price,$string];
                }else if($type==3){
                    return [$price,$string,$info['code']];
                }else if($type==4){
                    return [$price,$string,$price_us];
                }
                return $string;
            }
        }
        return $price;
    }

    static function numberFormat($price,$info=false){
        if(!$info){
            list(,,$info) = self::allDefaultCurr();
        }
        $decimal_place = (int)$info['decimal_place'];
        return number_format($price,$decimal_place,'.','');
    }

    static function codeFormat($price,$code){
        $currency_all = self::findAll();
        $info = [];
        foreach ($currency_all as $v){
            if($v['code']==$code){
                $info = $v;
            }
        }
        $price = self::numberFormat($price,$info);
        $string = self::_format($price,$info);
        return [$price,$string];
    }

    static function _format($price,$info=false){
        if(!$info){
            list(,,$info) = self::allDefaultCurr();
        }
        $string = '';
        if($info){
            if ($info['symbol_left']) {
                $string .= $info['symbol_left'];
            }
            $string .= $price;
            if ($info['symbol_right']) {
                $string .= $info['symbol_right'];
            }
        }
        return $string;
    }
}
