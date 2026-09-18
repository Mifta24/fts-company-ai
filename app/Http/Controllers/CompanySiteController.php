<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\SiteCopy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySiteController extends Controller
{
    private const PROJECTS_ON_PAGE = 9;

    public function show(Request $request): View
    {
        $company = Company::primary();

        abort_if(! $company, 404);

        $locale = in_array($request->query('lang'), Company::SUPPORTED_LOCALES, true)
            ? $request->query('lang')
            : $company->default_locale;

        app()->setLocale($locale);

        $allProjects = $company->projects()->where('is_active', true)->with('service')
            ->orderByDesc('is_featured')->orderBy('sort_order')->get();

        return view('site.show', [
            'company' => $company,
            'locale' => $locale,
            'copy' => SiteCopy::for($locale),
            'projects' => $allProjects->take(self::PROJECTS_ON_PAGE),
            'projectCount' => $allProjects->count(),
            'industries' => $allProjects->take(self::PROJECTS_ON_PAGE)->pluck('industry')->filter()->unique()->values(),
        ]);
    }
}
