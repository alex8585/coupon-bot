<?php

namespace App\Http\Controllers;


use App\Models\Test;

use Inertia\Inertia;
use App\Exports\TagsExport;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class DashboardController extends Controller
{
    public function __construct()
    {
    }

    public function index()
    {


        //PDF::SetTitle('Hello World');
        //PDF::AddPage();
        //PDF::Write(0, 'Hello World');
        // PDF::AddPage();
        // PDF::Output('hello_world.pdf');

        // $data = "        <style>
        //         .page-break {
        //             page-break-after: always;
        //         }
        //         h1 {
        //             color: red;
        //         }
        //         </style>
        //         <h1>Page 1</h1>
        //         <div class='page-break'></div>
        //         <h1>Page 2</h1>";


        // $pdf = App::make('dompdf.wrapper');
        // $pdf->loadHTML($data);
        // return $pdf->stream();

        //$t = new TagsExport();
        //return Excel::download(new  TagsExport, 'tags.xlsx');
        return Inertia::render('Dashboard/Index');
    }
}
