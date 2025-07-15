<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Datacenter extends Model
{
    protected $fillable = [
        'code',
        'name',
        'city',
        'country',
        'continent',
        'latitude',
        'longitude',
    ];

    /**
     * Get the servers associated with the datacenter.
     */
    public function servers()
    {
        return $this->hasMany(Server::class);
    }
}
