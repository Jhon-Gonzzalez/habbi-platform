<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/** Textos legales. Públicos y sin sesión: cualquiera debe poder leerlos. */
class LegalController extends Controller
{
    public function terminos(): View
    {
        return view('legal.terminos');
    }

    public function privacidad(): View
    {
        return view('legal.privacidad');
    }
}
