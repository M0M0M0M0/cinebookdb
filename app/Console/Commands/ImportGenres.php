<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TmdbService;
use App\Models\Genre;
use Illuminate\Support\Facades\Log;
class ImportGenres extends Command
{
    protected $signature = 'tmdb:import-genres';
    protected $description = 'Import genres from TMDB API';

    public function handle(TmdbService $tmdb)
    {
        $data = $tmdb->getGenres();

        if (!empty($data['genres']) && is_array($data['genres'])) {
            
            foreach($data['genres'] as $g) {
                // Kiểm tra xem $g có id và name không
                if (!empty($g['id']) && !empty($g['name'])) {
                    Genre::updateOrCreate(
                        ['genre_id' => $g['id']],
                        ['name' => $g['name']]
                    );
                }
            }

            $this->info('Imported genres successfully!'); // Thông báo thành công

        } else {
            
            // Nếu không có key 'genres', báo lỗi và ghi log
            $this->error('Failed to import genres. Invalid data received from TMDB.');
            
            // Ghi lại toàn bộ phản hồi vào file log để bạn kiểm tra
            Log::error('TMDB Genre Import Failed', ['response' => $data]);
        }
    }
}
