<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramAdminChat extends Model
{
    protected $fillable = [
        'chat_id',
        'name',
        'username',
        'role',
        'is_active',
        'notify_orders',
        'notify_payments',
        'notify_stock',
        'notify_reports',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'notify_orders'   => 'boolean',
        'notify_payments' => 'boolean',
        'notify_stock'    => 'boolean',
        'notify_reports'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function can(string $action): bool
    {
        return match ($action) {
            'view_dashboard' => true,
            'manage_orders'  => in_array($this->role, ['super_admin', 'admin']),
            'manage_payments' => $this->role === 'super_admin',
            'manage_settings' => $this->role === 'super_admin',
            default          => false,
        };
    }
}
