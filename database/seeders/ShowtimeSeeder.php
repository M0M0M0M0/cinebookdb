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
            1218925, 1519168, 604079, 1280450, 1038392, 338969, 1257009, 507244, 1078605, 1246049, 1500536, 1311031, 1107216, 803796, 1284120, 980477, 402431, 940721, 674, 120
        ];

        $movies = Movie::whereIn('movie_id', $movieIds)->get();

        // 🧠 Group movies by genre categories
        $familyMovies   = $movies->filter(fn ($m) => $this->hasGenre($m, ['Animation', 'Family', 'Comedy']));
        $actionMovies   = $movies->filter(fn ($m) => $this->hasGenre($m, ['Action', 'Adventure', 'Science Fiction']));
        $dramaMovies    = $movies->filter(fn ($m) => $this->hasGenre($m, ['Drama', 'Romance']));
        $thrillerMovies = $movies->filter(fn ($m) => $this->hasGenre($m, ['Horror', 'Thriller', 'Crime']));

        // 🔥 Top movies (high rating) for prime time
        $primeTimeMovies = $movies->sortByDesc('vote_average')->take(10);

        $theaters = Theater::with('rooms')->get();
        $daysToGenerate = 4; // today + 3 days
        $allShowtimes = [];

        foreach ($theaters as $theater) {
            $this->command->info("🎭 Generating showtimes for Theater: {$theater->name}");

            foreach ($theater->rooms as $room) {
                for ($d = 0; $d < $daysToGenerate; $d++) {
                    $baseDate = Carbon::now()->startOfDay()->addDays($d);
                    $currentTime = $baseDate->copy()->hour(8)->minute(0); // Start at 8 AM

                    while ($currentTime->hour < 24) {
                        $hour = $currentTime->hour;

                        // ⏰ Determine gap time based on time of day
                        $gap = $this->getGapTime($hour);

                        // 🎯 Pick movie pool based on time
                        $pool = $this->getMoviePool(
                            $hour,
                            $familyMovies,
                            $actionMovies,
                            $dramaMovies,
                            $thrillerMovies,
                            $primeTimeMovies,
                            $movies
                        );

                        // 🎲 Higher chance of popular movies during prime time (17-22h)
                        $isPrimeTime = ($hour >= 17 && $hour < 22);
                        $movie = ($isPrimeTime && rand(1, 100) <= 70)
                            ? $primeTimeMovies->random()
                            : $pool->random();

                        $duration = $movie->duration ?? rand(90, 130);

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
                            'base_price' => 10, // $10.00 base price
                            'status'     => 'Available',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $currentTime = $nextStart;

                        // 🚀 During prime time, squeeze in more movies
                        if ($isPrimeTime && rand(1, 100) <= 60) {
                            $currentTime = $currentTime->copy()->subMinutes(5);
                        }
                    }
                }
            }
        }

        // 🧱 Bulk insert safely in chunks
        $chunkSize = 500;
        foreach (array_chunk($allShowtimes, $chunkSize) as $chunk) {
            DB::table('showtimes')->insert($chunk);
        }

        $this->command->info("✅ " . count($allShowtimes) . ' total showtimes seeded successfully!');
    }

    /**
     * ⏰ Get gap time between showtimes based on hour
     */
    private function getGapTime(int $hour): int
    {
        // Morning (8-12): Longer gaps, less traffic
        if ($hour < 12) {
            return rand(20, 30);
        }

        // Afternoon (12-17): Medium gaps
        if ($hour < 17) {
            return rand(15, 20);
        }

        // Prime Time (17-22): Shorter gaps to fit more showtimes
        if ($hour < 22) {
            return rand(10, 15);
        }

        // Late Night (22-24): Medium gaps
        return rand(15, 25);
    }

    /**
     * 🎬 Get appropriate movie pool based on time
     */
    private function getMoviePool(
        int $hour,
        $familyMovies,
        $actionMovies,
        $dramaMovies,
        $thrillerMovies,
        $primeTimeMovies,
        $movies
    ) {
        // Morning: Family-friendly
        if ($hour < 12) {
            return $familyMovies->count() ? $familyMovies : $movies;
        }

        // Afternoon: Action/Adventure
        if ($hour < 17) {
            return $actionMovies->count() ? $actionMovies : $movies;
        }

        // Prime Evening: All movies (popularity filter applied in main loop)
        if ($hour < 22) {
            return $movies;
        }

        // Late Night: Thriller/Horror
        return $thrillerMovies->count() ? $thrillerMovies : $movies;
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
}
