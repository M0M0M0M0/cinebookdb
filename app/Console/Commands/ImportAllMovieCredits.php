<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Cac;
use App\Models\Movie;

class ImportAllMovieCredits extends Command
{
    protected $signature = 'tmdb:import-all-credits';
    protected $description = 'Import cast & crew for all movies in database from TMDB';

    public function handle()
    {
        $baseUrl = env('TMDB_BASE');
        $apiKey  = env('TMDB_KEY');

        if (!$baseUrl || !$apiKey) {
            $this->error('Chưa cấu hình TMDB_BASE hoặc TMDB_KEY trong .env');
            return 1;
        }

        $movies = Movie::all();
        $this->info('Đang import cast & crew cho '. $movies->count() .' movies...');

        foreach ($movies as $movie) {
            $this->info("Import movie: {$movie->title} (TMDB ID: {$movie->movie_id})");

            $url = "{$baseUrl}/movie/{$movie->movie_id}/credits?api_key={$apiKey}&language=en-US";
            $response = Http::get($url);

            if (!$response->ok()) {
                $this->error("Lỗi API TMDB cho movie {$movie->movie_id}: ".$response->status());
                continue;
            }

            $data = $response->json();

            $insertCount = 0;

            // Cast
            foreach ($data['cast'] ?? [] as $cast) {
                $cac = Cac::updateOrCreate(
                    ['tmdb_id' => $cast['id']],
                    [
                        'adult' => $cast['adult'] ?? false,
                        'gender' => $cast['gender'] ?? null,
                        'known_for_department' => $cast['known_for_department'] ?? null,
                        'name' => $cast['name'] ?? null,
                        'original_name' => $cast['original_name'] ?? null,
                        'popularity' => $cast['popularity'] ?? null,
                        'profile_path' => $cast['profile_path'] ?? null,
                        'character' => $cast['character'] ?? null,
                        'credit_id' => $cast['credit_id'] ?? null,
                        'cast_order' => $cast['order'] ?? null,
                        'department' => null,
                        'job' => null,
                    ]
                );

                $movie->cacs()->syncWithoutDetaching([
                    $cac->cac_id => [
                        'role_type' => 'cast',
                        'credit_id' => $cast['credit_id'] ?? null,
                        'cast_order' => $cast['order'] ?? null,
                        'character' => $cast['character'] ?? null,
                    ]
                ]);

                $insertCount++;
            }

            // Crew
            foreach ($data['crew'] ?? [] as $crew) {
                $cac = Cac::updateOrCreate(
                    ['tmdb_id' => $crew['id']],
                    [
                        'adult' => $crew['adult'] ?? false,
                        'gender' => $crew['gender'] ?? null,
                        'known_for_department' => $crew['known_for_department'] ?? null,
                        'name' => $crew['name'] ?? null,
                        'original_name' => $crew['original_name'] ?? null,
                        'popularity' => $crew['popularity'] ?? null,
                        'profile_path' => $crew['profile_path'] ?? null,
                        'character' => null,
                        'credit_id' => $crew['credit_id'] ?? null,
                        'cast_order' => null,
                        'department' => $crew['department'] ?? null,
                        'job' => $crew['job'] ?? null,
                    ]
                );

                $movie->cacs()->syncWithoutDetaching([
                    $cac->cac_id => [
                        'role_type' => 'crew',
                        'credit_id' => $crew['credit_id'] ?? null,
                        'department' => $crew['department'] ?? null,
                        'job' => $crew['job'] ?? null,
                    ]
                ]);

                $insertCount++;
            }

            $this->info("✔ Hoàn tất {$insertCount} cast & crew cho movie {$movie->title}");
        }

        $this->info('🎉 Hoàn tất import tất cả movie credits!');
        return 0;
    }
}
