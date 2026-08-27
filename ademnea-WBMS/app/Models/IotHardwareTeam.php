<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IotHardwareTeam extends Model
{
    use HasFactory;

    protected $table = 'iot_hardware_teams';

    protected $fillable = [
        'name',
        'country',
        'contact_email',
        'contact_phone',
        
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(IotDevice::class, 'hardware_team_id');
    }
    
    public function members(): HasMany
    {
        return $this->hasMany(IotHardwareTeamMember::class, 'hardware_team_id');
    }

}