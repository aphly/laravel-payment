<?php

namespace Aphly\LaravelPayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Aphly\Laravel\Models\Model;
use Illuminate\Support\Facades\Cache;

class MethodParams extends Model
{
    use HasFactory;
    protected $table = 'payment_method_params';
    protected $primaryKey = 'id';


    protected $fillable = [
        'key','val','method_id'
    ];

    public function findAll() {
        return Cache::rememberForever('group', function () {
            return self::get()->keyBy('id')->toArray();
        });
    }


}
