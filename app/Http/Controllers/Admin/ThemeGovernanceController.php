<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeGovernance;
use App\Services\ThemeGovernanceService;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * ThemeGovernanceController — Platform Owner theme lifecycle management (T7).
 *
 * Lists every curated theme with its effective status/version and lets the
 * owner move it between active / deprecated / hidden (with an optional
 * replacement). Existing stores keep rendering regardless; only new selection
 * is gated. Every change is audited.
 */
class ThemeGovernanceController extends Controller
{
    public function __construct(
        protected ThemeGovernanceService $governance,
    ) {}

    public function index(): View
    {
        return view('admin.theme-governance.index', [
            'themes'  => $this->governance->list(),
            'allIds'  => ThemeRegistry::ids(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_id'       => ['required', 'string', 'max:60'],
            'status'         => ['required', Rule::in(ThemeGovernance::STATUSES)],
            'replacement_id' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $this->governance->setStatus(
                $validated['theme_id'],
                $validated['status'],
                $validated['replacement_id'] ?: null,
                $request->user(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['theme_id' => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Theme '{$validated['theme_id']}' set to {$validated['status']}.");
    }
}
