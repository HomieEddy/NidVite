<?php

namespace App\Http\Controllers;

use App\Actions\Reports\GetPublicReportStatsAction;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(GetPublicReportStatsAction $getPublicReportStats): View
    {
        return view('welcome', $getPublicReportStats(app()->getLocale()));
    }
}
