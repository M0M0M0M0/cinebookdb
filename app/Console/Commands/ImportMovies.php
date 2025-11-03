<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TmdbService;
use App\Models\Movie;
use App\Models\Genre;

class ImportMovies extends Command
{
    protected $signature = 'tmdb:import-movies';
    protected $description = 'Import movies (now_playing + coming soon) from TMDB';

    public function handle(TmdbService $tmdb)
    {
        $this->info("Fetching multiple pages...");

        $ids = [];


        $nowPlayingPage = $tmdb->call('/movie/now_playing', ['page' => 1]);
        foreach ($nowPlayingPage['results'] as $m) {
            $ids[] = $m['id'];
        }

        $comingSoonPage = $tmdb->call('/movie/upcoming', ['page' => 1]);
        foreach ($comingSoonPage['results'] as $m) {
            $ids[] = $m['id'];
        }

        $ids = array_unique($ids);


        $this->info("Total unique movies: " . count($ids));

        foreach ($ids as $id) {
            $this->info("Import movie id = $id ...");
            $detail  = $tmdb->call("/movie/$id");
            $videos  = $tmdb->call("/movie/$id/videos");
            $trailer = null;
            if (!empty($videos['results'])) {
                foreach ($videos['results'] as $v) {
                    if ($v['type'] === 'Trailer' && $v['site'] === 'YouTube') {
                        $trailer = "https://www.youtube.com/watch?v=" . $v['key'];
                        break;
                    }
                }
            }
            $baseImageUrl = 'https://image.tmdb.org/t/p/original';
            $movie = Movie::updateOrCreate(
                ['movie_id' => $detail['id']],
                [
                    'original_language' => $detail['original_language'],
                    'original_title'    => $detail['original_title'],
                    'overview'          => $detail['overview'] ?? null,
                    'poster_path'       => !empty($detail['poster_path']) ? $baseImageUrl . $detail['poster_path'] : null,
                    'backdrop_path'     => !empty($detail['backdrop_path']) ? $baseImageUrl . $detail['backdrop_path'] : null,
                    'release_date'      => $detail['release_date'] ?? null,
                    'title'             => $detail['title'],
                    'vote_average'      => $detail['vote_average'] ?? null,
                    'duration'          => $detail['runtime'] ?? null,
                    'trailer_link'      => $trailer,
                ]
            );
            if (!empty($detail['genres']) && is_array($detail['genres'])) {
                $genreIds = collect($detail['genres'])->pluck('id')->filter()->all();
                if (!empty($genreIds)) {
                    $movie->genres()->sync($genreIds);
                }
            }
        }

        $this->info("DONE.");
    }
}
