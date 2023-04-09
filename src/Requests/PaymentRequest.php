<?php

namespace Aphly\LaravelPayment\Requests;

use Aphly\Laravel\Requests\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{

    public function rules()
    {
        if($this->isMethod('post')){
            $str = $this->route()->getAction()['controller'];
            list($routeControllerName, $routeActionName) = explode('@',$str);
            if($routeActionName=='register'){
                $where = ['id'=>$this->id,'id_type'=>'email'];
                return [
                    'id'   =>['required','email:filter',
                        Rule::unique('user_auth')->where(function ($query) use ($where){
                            return $query->where($where);
                        })
                    ],
                    'password'   =>['required','between:6,64','alpha_num','confirmed'],
                    'password_confirmation'   =>['required','same:password'],
                ];
            }else if($routeActionName=='login'){
                return [
                    'id' => 'required|email:filter',
                    'password' => 'required|between:6,64|alpha_num',
                ];
            }else if($routeActionName=='forget'){
                return [
                    'id' => 'required|email:filter'
                ];
            }else if($routeActionName=='forgetPassword'){
                return [
                    'password' => 'required|between:6,64|alpha_num',
                ];
            }
        }
        return [];
    }

    public function messages()
    {
        return [
            'id.required' => 'Please enter your email',
            'id.unique' => 'The email has already been taken.',
            'password.required' => 'Please enter your password',
            'password.alpha_num' => 'Password can only be letters and numbers',
        ];
    }


}
