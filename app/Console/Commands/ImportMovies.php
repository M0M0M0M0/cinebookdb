<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TmdbService;
use App\Models\Movie;

class ImportMovies extends Command
{
    protected $signature = 'tmdb:import-movies';
    protected $description = 'Import movies (now_playing + popular) from TMDB';

    public function handle(TmdbService $tmdb)
    {
        $this->info("Fetching multiple pages...");

        $ids = [];

        for ($p = 1; $p <= 2; $p++) {
            $popularPage = $tmdb->call('/movie/popular', ['page' => $p]);
            foreach($popularPage['results'] as $m){
                $ids[] = $m['id'];
            }
        }

        for ($p = 1; $p <= 2; $p++) {
            $nowPlayingPage = $tmdb->call('/movie/now_playing', ['page' => $p]);
            foreach($nowPlayingPage['results'] as $m){
                $ids[] = $m['id'];
            }
        }

        $ids = array_unique($ids);


        $this->info("Total unique movies: " . count($ids));

        foreach ($ids as $id) {
            $this->info("Import movie id = $id ...");

            $detail = $tmdb->call("/movie/$id");

            Movie::updateOrCreate(
                ['movie_id' => $detail['id']],
                [
                    'original_language' => $detail['original_language'],
                    'original_title'    => $detail['original_title'],
                    'overview'          => $detail['overview'] ?? null,
                    'poster_path'       => $detail['poster_path'] ?? null,
                    'release_date'      => $detail['release_date'] ?? null,
                    'title'             => $detail['title'],
                    'vote_average'      => $detail['vote_average'] ?? null,
                    'duration'          => $detail['runtime'] ?? null,
                    'trailer_link'      => null 
                ]
            );

        }

        $this->info("DONE.");
    }
}
