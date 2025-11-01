<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TmdbService;
use App\Models\Genre;

class ImportGenres extends Command
{
    protected $signature = 'tmdb:import-genres';
    protected $description = 'Import genres from TMDB API';

    public function handle(TmdbService $tmdb)
    {
        $data = $tmdb->getGenres();

        foreach($data['genres'] as $g) {
            Genre::updateOrCreate(
                ['genre_id' => $g['id']],
                ['name' => $g['name']]
            );
        }

        $this->info("Genres imported OK");
    }
}
