<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Utils\DayBoundaries;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceController extends Controller {
    public function __construct(
        private readonly DayBoundaries $boundaries,
    ) {
    }

    public function index(Request $request): View {
        $date = (string) ($request->query('date') ?? $this->boundaries->todayYmd());
        $window = max(1, min(6, (int) ($request->query('window') ?? 1)));

        return view('prices', [
            'selectedDate' => $date,
            'windowHours' => $window,
            'today' => $this->boundaries->todayYmd(),
        ]);
    }
}
