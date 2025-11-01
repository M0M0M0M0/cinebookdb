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
    public function getGenres()
    {
    return Http::get("{$this->base}/genre/movie/list", [
        'api_key' => $this->key,
        'language' => 'en-US'
    ])->json();
    }
    public function getNowPlaying($page = 1)
    {
    return Http::get("{$this->base}/movie/now_playing", [
        'api_key' => $this->key,
        'language' => 'en-US',
        'page' => $page,
        'region' => 'US'
    ])->json();
    }
    public function call($endpoint, $params = [])
{
    $params['api_key'] = $this->key;
    $params['language'] = $params['language'] ?? 'en-US';

    return Http::get("{$this->base}{$endpoint}", $params)->json();
}



}

