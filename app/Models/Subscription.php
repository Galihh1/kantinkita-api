<?php
namespace App\Models;

class Subscription extends BaseModel
{
    protected $casts = [
        'billing_start' => 'date',
        'billing_end'   => 'date',
        'amount'        => 'float',
    ];

    public function tenant()   { return $this->belongsTo(Tenant::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    public function isActive(): bool
    {
        return $this->billing_status === 'active' && $this->billing_end >= now()->toDateString();
    }

    public function isExpiringSoon(): bool
    {
        return $this->billing_end && now()->diffInDays($this->billing_end, false) <= 7;
    }

    /**
     * Get available authorities/permissions for this subscription plan.
     */
    public function getPlanPermissions(): array
    {
        $permissions = [
            'view_basic_reports' => true,
            'manage_menus'       => true,
            'manage_orders'      => true,
        ];

        if ($this->plan === 'professional' || $this->plan === 'enterprise') {
            $permissions['view_advanced_reports'] = true;
            $permissions['manage_multiple_staff'] = true;
            $permissions['custom_branding']       = true;
        }

        if ($this->plan === 'enterprise') {
            $permissions['api_access'] = true;
            $permissions['custom_limits'] = true;
        }

        return $permissions;
    }
}
