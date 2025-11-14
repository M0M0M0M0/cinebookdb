<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // ✅ 1. Tổng quan thống kê
    public function getOverviewStats()
    {
        $today = Carbon::today();
        $lastWeek = Carbon::today()->subWeek();

        // ✅ Tổng số booking completed
        $totalBookings = DB::table('bookings')
            ->where('status', 'completed')
            ->count();

        // ✅ Booking hôm nay
        $todayBookings = DB::table('bookings')
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->count();

        // ✅ Booking tuần này
        $weekBookings = DB::table('bookings')
            ->where('status', 'completed')
            ->where('created_at', '>=', $lastWeek)
            ->count();

        // ✅ TÍNH TỔNG DOANH THU từ bảng tickets
        $totalRevenue = DB::table('tickets')
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.booking_id')
            ->where('bookings.status', 'completed')
            ->sum('tickets.final_ticket_price');

        // ✅ Doanh thu hôm nay
        $todayRevenue = DB::table('tickets')
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.booking_id')
            ->where('bookings.status', 'completed')
            ->whereDate('bookings.created_at', $today)
            ->sum('tickets.final_ticket_price');

        // ✅ Tổng số user
        $totalUsers = DB::table('web_users')->count();

        // ✅ User đăng ký tuần này
        $weekUsers = DB::table('web_users')
            ->where('created_at', '>=', $lastWeek)
            ->count();

        // ✅ Tính % thay đổi
        $lastWeekBookings = DB::table('bookings')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$lastWeek->copy()->subWeek(), $lastWeek])
            ->count();

        $bookingsChange = $lastWeekBookings > 0
            ? round((($weekBookings - $lastWeekBookings) / $lastWeekBookings) * 100, 1)
            : 0;

        return response()->json([
            'total_bookings' => $totalBookings,
            'today_bookings' => $todayBookings,
            'week_bookings' => $weekBookings,
            'bookings_change_percent' => $bookingsChange,
            'total_revenue' => round($totalRevenue, 2),
            'today_revenue' => round($todayRevenue, 2),
            'total_users' => $totalUsers,
            'week_users' => $weekUsers,
        ]);
    }

    // ✅ 2. Doanh số theo ngày (7 ngày gần nhất)
    public function getDailySales()
    {
        $sales = DB::table('bookings')
            ->join('tickets', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->select(
                DB::raw('DATE(bookings.created_at) as date'),
                DB::raw('COUNT(DISTINCT bookings.booking_id) as bookings'),
                DB::raw('SUM(tickets.final_ticket_price) as revenue')
            )
            ->where('bookings.status', 'completed')
            ->where('bookings.created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($sales);
    }

    // ✅ 3. Doanh số theo tuần (4 tuần gần nhất)
    public function getWeeklySales()
    {
        $sales = DB::table('bookings')
            ->join('tickets', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->select(
                DB::raw('YEARWEEK(bookings.created_at) as week'),
                DB::raw('COUNT(DISTINCT bookings.booking_id) as bookings'),
                DB::raw('SUM(tickets.final_ticket_price) as revenue')
            )
            ->where('bookings.status', 'completed')
            ->where('bookings.created_at', '>=', Carbon::now()->subWeeks(4))
            ->groupBy('week')
            ->orderBy('week', 'asc')
            ->get();

        $sales = $sales->map(function ($item) {
            $year = substr($item->week, 0, 4);
            $week = substr($item->week, 4);
            $item->week_label = "Week {$week}";
            return $item;
        });

        return response()->json($sales);
    }

    // ✅ 4. Doanh số theo tháng (6 tháng gần nhất)
    public function getMonthlySales()
    {
        $sales = DB::table('bookings')
            ->join('tickets', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->select(
                DB::raw('DATE_FORMAT(bookings.created_at, "%Y-%m") as month'),
                DB::raw('COUNT(DISTINCT bookings.booking_id) as bookings'),
                DB::raw('SUM(tickets.final_ticket_price) as revenue')
            )
            ->where('bookings.status', 'completed')
            ->where('bookings.created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $sales = $sales->map(function ($item) {
            $item->month_label = Carbon::parse($item->month . '-01')->format('M Y');
            return $item;
        });

        return response()->json($sales);
    }

    // ✅ 5. Top phim bán chạy nhất
    public function getTopMovies(Request $request)
    {
        $limit = $request->input('limit', 10);

        $topMovies = DB::table('bookings')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.showtime_id')
            ->join('movies', 'showtimes.movie_id', '=', 'movies.movie_id')
            ->join('tickets', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->select(
                'movies.movie_id',
                'movies.title',
                'movies.poster_path',
                DB::raw('COUNT(DISTINCT bookings.booking_id) as total_bookings'),
                DB::raw('SUM(tickets.final_ticket_price) as total_revenue')
            )
            ->where('bookings.status', 'completed')
            ->groupBy('movies.movie_id', 'movies.title', 'movies.poster_path')
            ->orderBy('total_bookings', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($topMovies);
    }

    // ✅ 6. Doanh thu theo rạp
    public function getRevenueByTheater()
    {
        $revenue = DB::table('bookings')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.showtime_id')
            ->join('rooms', 'showtimes.room_id', '=', 'rooms.room_id')
            ->join('theaters', 'rooms.theater_id', '=', 'theaters.theater_id')
            ->join('tickets', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->select(
                'theaters.theater_id',
                'theaters.theater_name',
                'theaters.theater_city',
                DB::raw('COUNT(DISTINCT bookings.booking_id) as total_bookings'),
                DB::raw('SUM(tickets.final_ticket_price) as total_revenue')
            )
            ->where('bookings.status', 'completed')
            ->groupBy('theaters.theater_id', 'theaters.theater_name', 'theaters.theater_city')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return response()->json($revenue);
    }

    // ✅ 7. User đăng ký theo thời gian
    public function getUserRegistrations(Request $request)
    {
        $period = $request->input('period', 'daily');

        if ($period === 'daily') {
            $registrations = DB::table('web_users')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
        } elseif ($period === 'weekly') {
            $registrations = DB::table('web_users')
                ->select(
                    DB::raw('YEARWEEK(created_at) as week'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', Carbon::now()->subWeeks(12))
                ->groupBy('week')
                ->orderBy('week', 'asc')
                ->get();
        } else {
            $registrations = DB::table('web_users')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', Carbon::now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get();
        }

        return response()->json($registrations);
    }

    // ✅ 8. Active bookings
    public function getActiveBookings()
    {
        $activeBookings = DB::table('bookings')
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.showtime_id')
            ->join('movies', 'showtimes.movie_id', '=', 'movies.movie_id')
            ->join('web_users', 'bookings.web_user_id', '=', 'web_users.web_user_id')
            ->leftJoin('tickets', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->select(
                'bookings.booking_id',
                'bookings.status',
                'bookings.created_at',
                'bookings.expires_at',
                'movies.title as movie_title',
                'web_users.full_name as user_name',
                DB::raw('SUM(tickets.final_ticket_price) as total_amount')
            )
            ->whereIn('bookings.status', ['pending'])
            ->groupBy(
                'bookings.booking_id',
                'bookings.status',
                'bookings.created_at',
                'bookings.expires_at',
                'movies.title',
                'web_users.full_name'
            )
            ->orderBy('bookings.created_at', 'desc')
            ->get();

        return response()->json($activeBookings);
    }
    public function exportSales($period)
    {
        // Tắt strict mode cho query này
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

        $query = DB::table('bookings as b')
            ->join('tickets as t', 'b.booking_id', '=', 't.booking_id')
            ->where('b.status', 'completed');

        switch ($period) {
            case 'daily':
                $data = $query
                    ->selectRaw('DATE(b.created_at) as date')
                    ->selectRaw('COUNT(DISTINCT b.booking_id) as bookings')
                    ->selectRaw('SUM(t.final_ticket_price) as revenue')
                    ->where('b.created_at', '>=', Carbon::now()->subDays(30))
                    ->groupByRaw('DATE(b.created_at)')
                    ->orderByRaw('DATE(b.created_at) DESC')
                    ->get();
                break;

            case 'weekly':
                $data = DB::select("
                SELECT 
                    YEAR(b.created_at) as year,
                    WEEK(b.created_at, 1) as week_number,
                    CONCAT('Week ', WEEK(b.created_at, 1), ' - ', YEAR(b.created_at)) as week_label,
                    MIN(DATE(b.created_at)) as week_start,
                    MAX(DATE(b.created_at)) as week_end,
                    COUNT(DISTINCT b.booking_id) as bookings,
                    SUM(t.final_ticket_price) as revenue
                FROM bookings b
                INNER JOIN tickets t ON b.booking_id = t.booking_id
                WHERE b.status = 'completed'
                AND b.created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
                GROUP BY YEAR(b.created_at), WEEK(b.created_at, 1)
                ORDER BY YEAR(b.created_at) DESC, WEEK(b.created_at, 1) DESC
            ");
                break;

            case 'monthly':
                $data = DB::select("
                SELECT 
                    YEAR(b.created_at) as year,
                    MONTH(b.created_at) as month_number,
                    DATE_FORMAT(b.created_at, '%M %Y') as month_label,
                    COUNT(DISTINCT b.booking_id) as bookings,
                    SUM(t.final_ticket_price) as revenue
                FROM bookings b
                INNER JOIN tickets t ON b.booking_id = t.booking_id
                WHERE b.status = 'completed'
                AND b.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY YEAR(b.created_at), MONTH(b.created_at)
                ORDER BY YEAR(b.created_at) DESC, MONTH(b.created_at) DESC
            ");
                break;

            default:
                return response()->json(['error' => 'Invalid period'], 400);
        }

        return response()->json($data);
    }

    /**
     * Convert array to CSV (nếu dùng Option 2)
     */
    private function arrayToCSV($data, $columns)
    {
        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, array_values($columns));

        // Rows
        foreach ($data as $row) {
            $line = [];
            foreach (array_keys($columns) as $key) {
                $line[] = $row->$key ?? '';
            }
            fputcsv($output, $line);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
