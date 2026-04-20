<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $categories = ExpenseCategory::query()
            ->with('parent:id,name_ar')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name_ar', 'like', '%' . $search . '%')
                        ->orWhere('name_en', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('finance.expenses.categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        $parents = ExpenseCategory::query()->orderBy('code')->get(['id', 'code', 'name_ar']);
        $nextCode = ExpenseCategory::generateNextCodeForUser((int) (auth()->id() ?? 1));

        return view('finance.expenses.categories.create', compact('parents', 'nextCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('user_id', $uid)],
            'is_taxable' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['user_id'] = $uid;
        $data['code'] = ExpenseCategory::generateNextCodeForUser($uid);
        $data['is_taxable'] = $request->boolean('is_taxable');

        ExpenseCategory::query()->create($data);

        return redirect()
            ->route('finance.expenses.categories.index')
            ->with('success', 'تم إنشاء تصنيف المصروف بنجاح.');
    }

    public function edit(ExpenseCategory $category): View
    {
        $parents = ExpenseCategory::query()
            ->whereKeyNot($category->id)
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar']);

        return view('finance.expenses.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('user_id', $uid),
                Rule::notIn([$category->id]),
            ],
            'is_taxable' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (! empty($data['parent_id'])) {
            $walk = (int) $data['parent_id'];
            while ($walk > 0) {
                if ($walk === $category->id) {
                    return back()
                        ->withInput()
                        ->with('error', 'لا يمكن جعل التصنيف أباً لنفسه أو لسلسلة أبناء.');
                }
                $walk = (int) (ExpenseCategory::query()->whereKey($walk)->value('parent_id') ?? 0);
            }
        }

        $data['is_taxable'] = $request->boolean('is_taxable');
        $category->update($data);

        return redirect()
            ->route('finance.expenses.categories.index')
            ->with('success', 'تم تحديث تصنيف المصروف بنجاح.');
    }

    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return redirect()
                ->route('finance.expenses.categories.index')
                ->with('error', 'لا يمكن حذف تصنيف مرتبط بتصنيفات فرعية. احذف أو انقل الفرع أولاً.');
        }

        if (Payment::query()->where('expense_category_id', $category->id)->exists()) {
            return redirect()
                ->route('finance.expenses.categories.index')
                ->with('error', 'لا يمكن حذف تصنيف مرتبط بمصروفات مسجّلة.');
        }

        $category->delete();

        return redirect()
            ->route('finance.expenses.categories.index')
            ->with('success', 'تم حذف تصنيف المصروف بنجاح.');
    }
}
