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
        $daysToGenerate = 4; // today + next 3 days
        $chunkSize = 500;    // safe insert chunk

        // 🎬 Always take the latest 20 movies (top "Now Showing")
        $movies = Movie::orderByDesc('created_at')->take(20)->get();

        // Categorize by genre (for realistic scheduling)
        $familyMovies   = $movies->filter(fn($m) => $this->hasGenre($m, ['Animation', 'Family', 'Comedy']));
        $actionMovies   = $movies->filter(fn($m) => $this->hasGenre($m, ['Action', 'Adventure', 'Sci-Fi']));
        $dramaMovies    = $movies->filter(fn($m) => $this->hasGenre($m, ['Drama', 'Romance']));
        $thrillerMovies = $movies->filter(fn($m) => $this->hasGenre($m, ['Horror', 'Thriller', 'Crime']));

        $theaters = Theater::with('rooms')->get();
        $newShowtimes = [];

        foreach ($theaters as $theater) {
            $this->command->info("🎭 Generating/updating showtimes for Theater: {$theater->name}");

            foreach ($theater->rooms as $room) {
                for ($d = 0; $d < $daysToGenerate; $d++) {
                    $baseDate = Carbon::now()->startOfDay()->addDays($d);

                    // Get which movies already have showtimes in this room on that date
                    $existingMovieIds = DB::table('showtimes')
                        ->where('room_id', $room->room_id)
                        ->whereDate('start_time', $baseDate->toDateString())
                        ->pluck('movie_id')
                        ->toArray();

                    // 🎯 Remaining movies (not yet scheduled)
                    $missingMovies = $movies->whereNotIn('movie_id', $existingMovieIds);

                    $currentTime = $baseDate->copy()->hour(8)->minute(0);
                    $dailySlots = rand(6, 9);

                    for ($i = 0; $i < $dailySlots; $i++) {
                        // Pick from missing ones first; if empty, cycle through all
                        $movie = $missingMovies->isNotEmpty()
                            ? $missingMovies->random()
                            : $movies->random();

                        // Decide genre-based time block
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

                        if (!$pool->contains('movie_id', $movie->movie_id)) {
                            $movie = $pool->random();
                        }

                        $duration = $movie->duration ?? rand(90, 130);
                        $gap = rand(15, 20);
                        $endTime = $currentTime->copy()->addMinutes($duration);
                        $nextStart = $endTime->copy()->addMinutes($gap);

                        if ($endTime->greaterThan($baseDate->copy()->addDay()->hour(3))) break;

                        // Prevent duplicate showtimes for the same movie/time/room
                        $exists = DB::table('showtimes')
                            ->where('room_id', $room->room_id)
                            ->where('movie_id', $movie->movie_id)
                            ->whereBetween('start_time', [$baseDate, $baseDate->copy()->endOfDay()])
                            ->exists();

                        if ($exists) continue;

                        $newShowtimes[] = [
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

        // ✅ Insert in chunks to avoid placeholder limit
        foreach (array_chunk($newShowtimes, $chunkSize) as $chunk) {
            DB::table('showtimes')->insert($chunk);
        }

        $this->command->info(count($newShowtimes) . ' new showtimes added or cycled for top 20 movies!');
    }

    private function hasGenre($movie, array $genres): bool
    {
        if (!isset($movie->genres) || !is_iterable($movie->genres)) return false;
        foreach ($movie->genres as $g) {
            if (in_array(strtolower($g['name'] ?? ''), array_map('strtolower', $genres))) {
                return true;
            }
        }
        return false;
    }

    private function getDynamicPrice(int $hour): int
    {
        if ($hour < 12) return rand(80, 100) * 1000;
        if ($hour < 17) return rand(100, 120) * 1000;
        if ($hour < 22) return rand(120, 150) * 1000;
        return rand(100, 130) * 1000;
    }
}
