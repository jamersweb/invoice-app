<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiskGrade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiskGradeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/RiskGrades');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:risk_grades,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_rate_adjustment' => ['nullable', 'numeric'],
            'max_funding_limit' => ['nullable', 'numeric', 'min:0'],
            'max_tenor_days' => ['nullable', 'integer', 'min:0'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $riskGrade = RiskGrade::create($validated);
        return response()->json($riskGrade, 201);
    }

    public function update(Request $request, RiskGrade $riskGrade)
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:10', 'unique:risk_grades,code,' . $riskGrade->id],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_rate_adjustment' => ['nullable', 'numeric'],
            'max_funding_limit' => ['nullable', 'numeric', 'min:0'],
            'max_tenor_days' => ['nullable', 'integer', 'min:0'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $riskGrade->update($validated);
        return response()->json($riskGrade);
    }

    public function destroy(RiskGrade $riskGrade)
    {
        $riskGrade->delete();
        return response()->json(['ok' => true]);
    }
}







