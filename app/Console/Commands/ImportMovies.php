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
        if (!empty($nowPlayingPage['results'])) {
            foreach ($nowPlayingPage['results'] as $m) {
                $ids[] = $m['id'];
            }
        }

        $comingSoonPage = $tmdb->call('/movie/upcoming', ['page' => 1]);
         if (!empty($comingSoonPage['results'])) {
            foreach ($comingSoonPage['results'] as $m) {
                $ids[] = $m['id'];
            }
        }

        $ids = array_unique($ids);


        $this->info("Total unique movies: " . count($ids));

        foreach ($ids as $id) {
            $this->info("Import movie id = $id ...");
            $detail  = $tmdb->call("/movie/$id");

            // --- BẮT ĐẦU SỬA LỖI (Kiểm tra Duration) ---

            // Lấy thời lượng (runtime), nếu không có (null) hoặc là 0, thì bỏ qua
            $duration = $detail['runtime'] ?? null;

            if (empty($duration) || $duration <= 0) {
                // Lấy title để log, nếu không có title thì dùng ID
                $titleForLog = $detail['title'] ?? "ID: $id";
                $this->warn("Skipping movie '$titleForLog' due to invalid duration: $duration");
                
                // Bỏ qua phim này và chuyển sang ID tiếp theo
                continue; 
            }
            // --- KẾT THÚC SỬA LỖI ---


            // Chỉ gọi API videos nếu duration hợp lệ (tiết kiệm API call)
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
                    
                    // Giờ biến $duration đã được kiểm tra, an toàn để lưu
                    'duration'          => $duration, 
                    
                    'trailer_link'      => $trailer,
                ]
            );

            if (!empty($detail['genres']) && is_array($detail['genres'])) {
                $genreIds = collect($detail['genres'])->pluck('id')->filter()->all();
                if (!empty($genreIds)) {
                    // --- SỬA LỖI ---
                    // Bỏ qua bước tìm 'id' cục bộ và đồng bộ trực tiếp bằng $genreIds (TMDB genre_id)
                    // Giả định rằng mối quan hệ `genres()` của bạn được thiết lập để
                    // sử dụng 'genre_id' làm khóa ngoại trong bảng pivot.
                    $movie->genres()->sync($genreIds);
                }
            }
        }

        $this->info("DONE.");
    }
}
