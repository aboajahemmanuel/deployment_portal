<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Book;
use App\Models\Payout;
use App\Models\WalletTransaction;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Prepare analytics data
        $analytics = $this->getDashboardAnalytics();
        
        return view('admin.dashboard', compact('analytics'));
    }

    public function unifiedDashboard(Request $request)
    {
        // Handle date filtering
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->subDays(30);
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now();
        
        // Get all data for the unified dashboard
        $overview = $this->getDashboardAnalytics();
        $analytics = $this->getAnalyticsData($request, $startDate, $endDate);
        $sales = $this->getSalesData($request, $startDate, $endDate);
        $topAuthors = $this->getTopAuthors($request, $startDate, $endDate);
        $topBooks = $this->getTopBooks($request, $startDate, $endDate);
        $genreData = $this->getGenrePerformance($request, $startDate, $endDate);
        $recentTransactions = $this->getRecentTransactions($startDate, $endDate);
        
        return view('admin.unified-dashboard', compact(
            'overview', 
            'analytics', 
            'sales', 
            'topAuthors', 
            'topBooks', 
            'genreData', 
            'recentTransactions'
        ));
    }

    private function getDashboardAnalytics()
    {
        // Get stats
        $totalUsers = User::count();
        $totalAuthors = User::whereHas('roles', function($query) {
            $query->where('name', 'author');
        })->count();
        
        $totalBooks = Book::count();
        $publishedBooks = Book::where('status', 'accepted')->count();
        $pendingBooks = Book::where('status', 'pending')->count();
        
        $totalRevenue = WalletTransaction::where('type', 'sale')->sum('amount');
        
        $pendingPayouts = Payout::where('status', 'pending')->count();
        $pendingPayoutAmount = Payout::where('status', 'pending')->sum('amount_requested');
        
        $approvedPayouts = Payout::where('status', 'approved')->count();
        $totalPayoutAmount = Payout::where('status', 'approved')->sum('amount_requested');
        
        // Get this month and last month revenue
        $thisMonthRevenue = WalletTransaction::where('type', 'sale')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
            
        $lastMonthRevenue = WalletTransaction::where('type', 'sale')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');
        
        // Calculate revenue growth
        $revenueGrowth = 0;
        if ($lastMonthRevenue > 0) {
            $revenueGrowth = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        }
        
        // Get recent data
        $recentBooks = Book::with('user')->orderBy('created_at', 'desc')->limit(5)->get();
        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        $recentPayouts = Payout::with('user')->orderBy('created_at', 'desc')->limit(5)->get();
        
        return [
            'stats' => [
                'total_users' => $totalUsers,
                'total_authors' => $totalAuthors,
                'total_revenue' => $totalRevenue,
                'revenue_growth' => $revenueGrowth,
                'total_books' => $totalBooks,
                'published_books' => $publishedBooks,
                'pending_books' => $pendingBooks,
                'pending_payouts' => $pendingPayouts,
                'pending_payout_amount' => $pendingPayoutAmount,
                'approved_payouts' => $approvedPayouts,
                'total_payout_amount' => $totalPayoutAmount,
                'this_month_revenue' => $thisMonthRevenue,
                'last_month_revenue' => $lastMonthRevenue,
            ],
            'recent' => [
                'books' => $recentBooks,
                'users' => $recentUsers,
                'payouts' => $recentPayouts,
            ]
        ];
    }

    private function getAnalyticsData($request, $startDate, $endDate)
    {
        return [
            'active_users' => User::where('last_login_at', '>=', $startDate)->count(),
            'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'book_views' => rand(5000, 15000), // Mock data
            'views_change' => 5.2,
            'conversion_rate' => 3.5,
            'author_retention' => 78.2,
            'avg_session_duration' => 12,
            'pages_per_session' => 3.4,
            'bounce_rate' => 45.2,
            'return_rate' => 32.1,
            'gross_revenue' => WalletTransaction::where('type', 'sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'platform_revenue' => WalletTransaction::where('type', 'sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount') * 0.15,
            'author_earnings' => WalletTransaction::where('type', 'sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount') * 0.85,
            'payouts_paid' => WalletTransaction::where('type', 'payout')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'chartData' => $this->getAnalyticsChartData($startDate, $endDate)
        ];
    }

    private function getSalesData($request, $startDate, $endDate)
    {
        // Get metrics for current period
        $currentMetrics = $this->getSalesMetrics($startDate, $endDate);
        
        // Get metrics for previous period (for comparison)
        $periodLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($periodLength);
        $prevEndDate = $startDate->copy()->subDay();
        $previousMetrics = $this->getSalesMetrics($prevStartDate, $prevEndDate);

        // Calculate percentage changes
        $metrics = [
            'total_revenue' => $currentMetrics['total_revenue'],
            'total_sales' => $currentMetrics['total_sales'],
            'avg_order_value' => $currentMetrics['avg_order_value'],
            'platform_commission' => $currentMetrics['platform_commission'],
            'commission_rate' => 15,
            'revenue_change' => $this->calculatePercentageChange($previousMetrics['total_revenue'], $currentMetrics['total_revenue']),
            'sales_change' => $this->calculatePercentageChange($previousMetrics['total_sales'], $currentMetrics['total_sales']),
            'aov_change' => $this->calculatePercentageChange($previousMetrics['avg_order_value'], $currentMetrics['avg_order_value']),
        ];

        return [
            'metrics' => $metrics,
            'chartData' => $this->getSalesChartData($startDate, $endDate)
        ];
    }

    private function getTopAuthors($request, $startDate, $endDate)
    {
        return User::select([
                'users.id',
                'users.name',
                'users.email',
                'users.created_at'
            ])
            ->selectRaw('SUM(wallet_transactions.amount) as total_earnings')
            ->selectRaw('COUNT(DISTINCT books.id) as books_count')
            ->join('books', 'users.id', '=', 'books.user_id')
            ->leftJoin('wallet_transactions', function($join) use ($startDate, $endDate) {
                $join->on('books.id', '=', 'wallet_transactions.book_id')
                     ->where('wallet_transactions.type', '=', 'sale')
                     ->whereBetween('wallet_transactions.created_at', [$startDate, $endDate]);
            })
            ->role('author')
            ->groupBy([
                'users.id',
                'users.name',
                'users.email',
                'users.created_at'
            ])
            ->orderByDesc('total_earnings')
            ->limit(10)
            ->get();
    }

    private function getTopBooks($request, $startDate, $endDate)
    {
        return Book::select([
                'books.id',
                'books.title',
                'books.user_id',
                'books.price',
                'books.genre',
                'books.status',
                'books.created_at'
            ])
            ->selectRaw('COUNT(wallet_transactions.id) as sales_count')
            ->selectRaw('SUM(wallet_transactions.amount) as total_revenue')
            ->leftJoin('wallet_transactions', function($join) use ($startDate, $endDate) {
                $join->on('books.id', '=', 'wallet_transactions.book_id')
                     ->where('wallet_transactions.type', '=', 'sale')
                     ->whereBetween('wallet_transactions.created_at', [$startDate, $endDate]);
            })
            ->with('user')
            ->groupBy([
                'books.id',
                'books.title',
                'books.user_id',
                'books.price',
                'books.genre',
                'books.status',
                'books.created_at'
            ])
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }

    private function getGenrePerformance($request, $startDate, $endDate)
    {
        $genreData = Book::select('genre')
            ->selectRaw('SUM(wallet_transactions.amount) as revenue')
            ->leftJoin('wallet_transactions', function($join) use ($startDate, $endDate) {
                $join->on('books.id', '=', 'wallet_transactions.book_id')
                     ->where('wallet_transactions.type', '=', 'sale')
                     ->whereBetween('wallet_transactions.created_at', [$startDate, $endDate]);
            })
            ->groupBy('genre')
            ->orderByDesc('revenue')
            ->get();

        return [
            'labels' => $genreData->pluck('genre')->toArray(),
            'data' => $genreData->pluck('revenue')->toArray(),
        ];
    }

    private function getRecentTransactions($startDate, $endDate)
    {
        return WalletTransaction::with(['book', 'user'])
            ->where('type', 'sale')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->limit(10)
            ->get();
    }

    private function getSalesMetrics($startDate, $endDate)
    {
        $totalRevenue = WalletTransaction::where('type', 'sale')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalSales = WalletTransaction::where('type', 'sale')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $avgOrderValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        $platformCommission = $totalRevenue * 0.15; // 15% commission

        return [
            'total_revenue' => $totalRevenue,
            'total_sales' => $totalSales,
            'avg_order_value' => $avgOrderValue,
            'platform_commission' => $platformCommission,
        ];
    }

    private function calculatePercentageChange($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    private function getSalesChartData($startDate, $endDate)
    {
        $days = [];
        $revenue = [];
        
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayRevenue = WalletTransaction::where('type', 'sale')
                ->whereDate('created_at', $currentDate)
                ->sum('amount');
            
            $days[] = $currentDate->format('M d');
            $revenue[] = $dayRevenue;
            
            $currentDate->addDay();
        }

        return [
            'labels' => $days,
            'revenue' => $revenue,
        ];
    }

    private function getAnalyticsChartData($startDate, $endDate)
    {
        $days = [];
        $users = [];
        $authors = [];
        $books = [];
        
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayUsers = User::whereDate('created_at', $currentDate)->count();
            $dayAuthors = User::role('author')->whereDate('created_at', $currentDate)->count();
            $dayBooks = Book::whereDate('created_at', $currentDate)->count();
            
            $days[] = $currentDate->format('M d');
            $users[] = $dayUsers;
            $authors[] = $dayAuthors;
            $books[] = $dayBooks;
            
            $currentDate->addDay();
        }

        return [
            'labels' => $days,
            'users' => $users,
            'authors' => $authors,
            'books' => $books,
        ];
    }

    public function userActivity()
    {
        return view('admin.user-activity');
    }

    // Test method for SweetAlert messages
    public function testSweetAlert(Request $request)
    {
        $type = $request->query('type', 'success');
        
        switch ($type) {
            case 'error':
                return redirect()->route('admin.dashboard')->with('error', 'This is a test error message!');
            case 'warning':
                return redirect()->route('admin.dashboard')->with('warning', 'This is a test warning message!');
            case 'info':
                return redirect()->route('admin.dashboard')->with('info', 'This is a test info message!');
            default:
                return redirect()->route('admin.dashboard')->with('success', 'This is a test success message!');
        }
    }
}