<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    protected $base;
    protected $key;

    public function __construct()
    {
        $this->base = config('services.tmdb.base');
        $this->key  = config('services.tmdb.key');
    }

    public function getMovie($id)
    {
        return Http::get("{$this->base}/movie/{$id}", [
            'api_key' => $this->key,
            'language' => 'en-US'
        ])->json();
    }
}

