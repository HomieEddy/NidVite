<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasReportCoordinates
{
    /**
     * Add latitude/longitude coordinate aliases to the select clause.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->selectRaw('ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude');
    }

    /**
     * Read report coordinates through one model-level boundary.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function coordinates(): ?array
    {
        if ($this->location === null) {
            return null;
        }

        if (isset($this->attributes['latitude'], $this->attributes['longitude'])) {
            return [
                'lat' => (float) $this->attributes['latitude'],
                'lng' => (float) $this->attributes['longitude'],
            ];
        }

        $point = static::query()
            ->whereKey($this->getKey())
            ->withCoordinates()
            ->first(['id']);

        if ($point === null || ! isset($point->latitude, $point->longitude)) {
            return null;
        }

        return [
            'lat' => (float) $point->latitude,
            'lng' => (float) $point->longitude,
        ];
    }

    /**
     * Read report coordinates as an object for view compatibility.
     */
    public function coordinatePoint(): ?object
    {
        $coordinates = $this->coordinates();

        if ($coordinates === null) {
            return null;
        }

        return (object) $coordinates;
    }
}
