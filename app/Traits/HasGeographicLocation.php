<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasGeographicLocation
{
    /**
     * Get the county relationship.
     */
    public function county(): BelongsTo
    {
        return $this->belongsTo(\App\Models\County::class);
    }

    /**
     * Get the constituency relationship.
     */
    public function constituency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Constituency::class);
    }

    /**
     * Get the ward relationship.
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Ward::class);
    }

    /**
     * Get the full geographic location as a string.
     */
    public function getGeographicLocationAttribute(): ?string
    {
        $parts = [];
        
        if ($this->ward) {
            $parts[] = $this->ward->name;
        }
        
        if ($this->constituency) {
            $parts[] = $this->constituency->name;
        }
        
        if ($this->county) {
            $parts[] = $this->county->name;
        }
        
        return !empty($parts) ? implode(', ', $parts) : null;
    }
}

