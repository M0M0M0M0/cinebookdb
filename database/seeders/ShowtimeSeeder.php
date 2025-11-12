<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Theater;
use App\Models\Movie;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        // 🎬 Now Showing movies (20 latest)
        $movieIds = [
            1033148, 760329, 1086260, 1218925, 986097, 604079, 1280450,
            1038392, 338969, 1257009, 507244, 1078605, 1246049, 1316147,
            1311031, 1107216, 803796, 1284120, 1186350, 402431
        ];

        $movies = Movie::whereIn('movie_id', $movieIds)->get();

        // 🧠 Group movies by genre categories for smarter assignment
        $familyMovies   = $movies->filter(fn($m) => $this->hasGenre($m, ['Animation', 'Family', 'Comedy']));
        $actionMovies   = $movies->filter(fn($m) => $this->hasGenre($m, ['Action', 'Adventure', 'Sci-Fi']));
        $dramaMovies    = $movies->filter(fn($m) => $this->hasGenre($m, ['Drama', 'Romance']));
        $thrillerMovies = $movies->filter(fn($m) => $this->hasGenre($m, ['Horror', 'Thriller', 'Crime']));

        $theaters = Theater::with('rooms')->get();
        $daysToGenerate = 4; // today + 3 days
        $allShowtimes = [];

        foreach ($theaters as $theater) {
            $this->command->info("🎭 Generating showtimes for Theater: {$theater->name}");

            foreach ($theater->rooms as $room) {
                for ($d = 0; $d < $daysToGenerate; $d++) {
                    $baseDate = Carbon::now()->startOfDay()->addDays($d);
                    $currentTime = $baseDate->copy()->hour(8)->minute(0);

                    // ~6–9 showtimes per day
                    $dailySlots = rand(6, 9);

                    for ($i = 0; $i < $dailySlots; $i++) {
                        // 🎯 Pick movie pool based on time of day
                        $hour = $currentTime->hour;
                        if ($hour < 12) {
                            $pool = $familyMovies->count() ? $familyMovies : $movies;
                        } elseif ($hour < 17) {
                            $pool = $actionMovies->count() ? $actionMovies : $movies;
                        } elseif ($hour < 22) {
                            $pool = $dramaMovies->count() ? $dramaMovies : $movies;
                        } else {
                            $pool = $thrillerMovies->count() ? $thrillerMovies : $movies;
                        }

                        $movie = $pool->random();
                        $duration = $movie->duration ?? rand(90, 130);
                        $gap = rand(15, 20);

                        $endTime = $currentTime->copy()->addMinutes($duration);
                        $nextStart = $endTime->copy()->addMinutes($gap);

                        // Stop if showtime goes past 3 AM next day
                        if ($endTime->greaterThan($baseDate->copy()->addDay()->hour(3))) {
                            break;
                        }

                        $allShowtimes[] = [
                            'movie_id'   => $movie->movie_id,
                            'room_id'    => $room->room_id,
                            'start_time' => $currentTime->format('Y-m-d H:i:s'),
                            'end_time'   => $endTime->format('Y-m-d H:i:s'),
                            'base_price' => $this->getDynamicPrice($hour),
                            'status'     => 'available',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $currentTime = $nextStart;
                    }
                }
            }
        }

        // 🧱 Bulk insert safely in chunks to avoid "too many placeholders" error
            $chunkSize = 500; // insert 500 showtimes per query

            foreach (array_chunk($allShowtimes, $chunkSize) as $chunk) {
                DB::table('showtimes')->insert($chunk);
            }

            $this->command->info(count($allShowtimes) . ' total showtimes seeded successfully!');

    }

    /**
     * Check if movie has a genre from list.
     */
    private function hasGenre($movie, array $genres): bool
    {
        if (!isset($movie->genres) || !is_iterable($movie->genres)) {
            return false;
        }
        foreach ($movie->genres as $g) {
            if (in_array(strtolower($g['name'] ?? ''), array_map('strtolower', $genres))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 🎟 Dynamic pricing based on time of day.
     */
    private function getDynamicPrice(int $hour): int
    {
        if ($hour < 12) return rand(80, 100) * 1000;  // Morning cheaper
        if ($hour < 17) return rand(100, 120) * 1000; // Afternoon midrange
        if ($hour < 22) return rand(120, 150) * 1000; // Evening premium
        return rand(100, 130) * 1000;                 // Late night moderate
    }
}
