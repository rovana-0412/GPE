<?php

namespace App\Http\Controllers;

use App\Exports\RapportPatrimoineExport;
use App\Services\ReportDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(ReportDataService $reports)
    {
        return view('reports.index', ['rapport' => $reports->rapport()]);
    }

    public function pdf(ReportDataService $reports): Response
    {
        $rapport = $reports->rapport();

        return Pdf::loadView('reports.pdf', compact('rapport'))
            ->setPaper('a4', 'portrait')
            ->download('rapport-patrimoine.pdf');
    }

    public function excel(ReportDataService $reports)
    {
        $rapport = $reports->rapport();

        return Excel::download(
            new RapportPatrimoineExport($rapport['indicateurs']),
            'rapport-patrimoine.xlsx',
        );
    }
}
