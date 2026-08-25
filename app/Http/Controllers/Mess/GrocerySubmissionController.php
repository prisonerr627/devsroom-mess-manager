<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mess\ApproveGrocerySubmissionRequest;
use App\Http\Requests\Mess\RejectGrocerySubmissionRequest;
use App\Models\ExpenseCategory;
use App\Models\GrocerySubmission;
use App\Services\GrocerySubmissionService;
use App\Support\ExpenseKind;
use App\Support\GroceryStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GrocerySubmissionController extends Controller
{
    public function __construct(private readonly GrocerySubmissionService $service) {}

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), GroceryStatus::ALL, true)
            ? $request->query('tab')
            : GroceryStatus::PENDING;

        $submissions = GrocerySubmission::query()
            ->with(['member', 'reviewedBy', 'expense'])
            ->where('status', $tab)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $counts = GrocerySubmission::query()
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status')
            ->all();

        $bazarCategories = ExpenseCategory::query()
            ->where('kind', ExpenseKind::BAZAR)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('mess.groceries.index', compact('submissions', 'counts', 'tab', 'bazarCategories'));
    }

    public function approve(ApproveGrocerySubmissionRequest $request, GrocerySubmission $grocerySubmission): RedirectResponse
    {
        $amount = $request->filled('amount') ? (float) $request->validated('amount') : null;

        $this->service->approve(
            $grocerySubmission,
            $request->user()->id,
            (int) $request->validated('expense_category_id'),
            $amount,
        );

        return redirect()
            ->route('mess.groceries.index', ['tab' => GroceryStatus::PENDING])
            ->with('success', __('Grocery purchase by :name approved and added to expenses.', ['name' => $grocerySubmission->member?->name ?? '—']));
    }

    public function reject(RejectGrocerySubmissionRequest $request, GrocerySubmission $grocerySubmission): RedirectResponse
    {
        $this->service->reject($grocerySubmission, $request->user()->id, $request->validated('rejection_reason'));

        return redirect()
            ->route('mess.groceries.index', ['tab' => GroceryStatus::PENDING])
            ->with('success', __('Grocery submission rejected.'));
    }
}
