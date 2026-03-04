<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email:strict',
            'password' => 'nullable|min:8',
            'transfer_enable' => 'numeric',
            'device_limit' => 'nullable|integer',
            'expired_at' => 'nullable|integer',
            'banned' => 'required|in:0,1',
            'plan_id' => 'nullable|integer',
            'commission_rate' => 'nullable|integer|min:0|max:100',
            'discount' => 'nullable|integer|min:0|max:100',
            'is_admin' => 'required|in:0,1',
            'is_staff' => 'required|in:0,1',
            'u' => 'integer',
            'd' => 'integer',
            'balance' => 'integer',
            'commission_type' => 'integer',
            'commission_balance' => 'integer',
            'remarks' => 'nullable',
            'speed_limit' => 'nullable|integer'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Email cannot be empty',
            'email.email' => 'Email format is incorrect',
            'transfer_enable.numeric' => 'Traffic format is incorrect',
            'device_limit.integer' => 'Device limit format is incorrect',
            'expired_at.integer' => 'Expiration time format is incorrect',
            'banned.required' => 'Banned status cannot be empty',
            'banned.in' => 'Banned status format is incorrect',
            'is_admin.required' => 'Admin status cannot be empty',
            'is_admin.in' => 'Admin status format is incorrect',
            'is_staff.required' => 'Staff status cannot be empty',
            'is_staff.in' => 'Staff status format is incorrect',
            'plan_id.integer' => 'Plan ID format is incorrect',
            'commission_rate.integer' => 'Commission rate format is incorrect',
            'commission_rate.nullable' => 'Commission rate format is incorrect',
            'commission_rate.min' => 'Commission rate minimum is 0',
            'commission_rate.max' => 'Commission rate maximum is 100',
            'discount.integer' => 'Discount rate format is incorrect',
            'discount.nullable' => 'Discount rate format is incorrect',
            'discount.min' => 'Discount rate minimum is 0',
            'discount.max' => 'Discount rate maximum is 100',
            'u.integer' => 'Upload traffic format is incorrect',
            'd.integer' => 'Download traffic format is incorrect',
            'balance.integer' => 'Balance format is incorrect',
            'commission_balance.integer' => 'Commission balance format is incorrect',
            'password.min' => 'Password must be at least 8 characters',
            'speed_limit.integer' => 'Speed limit format is incorrect'
        ];
    }
}
