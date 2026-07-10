<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotHardwareTeamMember extends Model
{
    protected $table = 'iot_hardware_team_members';

    protected $fillable = [
        'hardware_team_id', 'name', 'team_role', 'profession',
        'country', 'email', 'phone', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hardwareTeam(): BelongsTo
    {
        return $this->belongsTo(IotHardwareTeam::class, 'hardware_team_id');
    }
}