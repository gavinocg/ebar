<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    protected $fillable = [
        'name',
        'connection_type',
        'printer_type',
        'address',
        'port',
        'paper_width',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'port' => 'integer'
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true)->where('is_active', true);
    }

    public function isThermal()
    {
        return in_array($this->connection_type, ['bluetooth', 'wifi', 'lan']);
    }

    public function isNormal()
    {
        return $this->connection_type === 'normal';
    }
}
