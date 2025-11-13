<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TmdbService;
use App\Models\Movie;
use App\Models\Genre;


class ImportMovies extends Command
{
    protected $signature = 'tmdb:import-movies';
    protected $description = 'Import specific movies from TMDB by fixed IDs';

    public function handle(TmdbService $tmdb)
    {
        $ids = [
            1084242, 967941, 1242898, 1571470, 701387, 1306525, 1327862, 997113, 1086910,
            1197137, 1272166, 1156594, 1072699, 1386827, 1364608, 1290159, 1330421, 1218925,
            1519168, 604079, 1280450, 1038392, 338969, 1257009, 507244, 1078605, 1246049,
            1500536, 1311031, 1107216, 803796, 1284120, 980477, 402431, 940721, 674, 120,
        ];

        $this->info("Importing " . count($ids) . " specific movies...");

        $baseImageUrl = 'https://image.tmdb.org/t/p/original';

        foreach ($ids as $id) {
            $this->info("Importing movie ID = $id ...");

            $detail = $tmdb->call("/movie/$id");

            if (empty($detail) || !is_array($detail)) {
                $this->warn("Skipping ID $id: no valid data returned from TMDB.");
                continue;
            }

            // --- Kiểm tra Duration ---
            $duration = $detail['runtime'] ?? null;
            if (empty($duration) || $duration <= 0) {
                $titleForLog = $detail['title'] ?? "ID: $id";
                $this->warn("Skipping movie '$titleForLog' due to invalid duration: $duration");
                continue;
            }

            // --- Lấy trailer (nếu có) ---
            $videos  = $tmdb->call("/movie/$id/videos");
            $trailer = null;
            if (!empty($videos['results'])) {
                foreach ($videos['results'] as $v) {
                    if (($v['type'] ?? '') === 'Trailer' && ($v['site'] ?? '') === 'YouTube') {
                        $trailer = "https://www.youtube.com/watch?v=" . $v['key'];
                        break;
                    }
                }
            }

            // --- Lưu vào database ---
            $movie = Movie::updateOrCreate(
                ['movie_id' => $detail['id']],
                [
                    'original_language' => $detail['original_language'] ?? null,
                    'original_title'    => $detail['original_title'] ?? null,
                    'overview'          => $detail['overview'] ?? null,
                    'poster_path'       => !empty($detail['poster_path']) ? $baseImageUrl . $detail['poster_path'] : null,
                    'backdrop_path'     => !empty($detail['backdrop_path']) ? $baseImageUrl . $detail['backdrop_path'] : null,
                    'release_date'      => $detail['release_date'] ?? null,
                    'title'             => $detail['title'] ?? null,
                    'vote_average'      => $detail['vote_average'] ?? null,
                    'duration'          => $duration,
                    'trailer_link'      => $trailer,
                ]
            );

            // --- Đồng bộ thể loại ---
            if (!empty($detail['genres']) && is_array($detail['genres'])) {
                $genreIds = collect($detail['genres'])->pluck('id')->filter()->all();
                if (!empty($genreIds)) {
                    $movie->genres()->sync($genreIds);
                }
            }

            $this->info("✔ Imported: " . ($detail['title'] ?? "Unknown Title"));
        }

        $this->info("DONE importing all movies.");
    }
}
