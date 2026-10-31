<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with server-side pagination (30 items/page).
     * Default sort: created_at desc.
     */
    public function index(Request $request): View|JsonResponse
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc'));

        $allowedSorts = ['code', 'name', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $employees = Employee::query()
            ->orderBy($sortBy, $sortDir)
            ->paginate(30)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $employees,
            ]);
        }

        return view('settings.employees.index', [
            'employees' => $employees,
            'currentSortBy' => $sortBy,
            'currentSortDir' => $sortDir,
            'activeTab' => 'employees',
        ]);
    }

    /**
     * Check if employee code already exists (for blur validation).
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $ignoreId = $request->input('ignore_id');

        if ($code === '') {
            return response()->json(['exists' => false]);
        }

        $query = Employee::where('code', $code);
        if (! empty($ignoreId)) {
            $query->where('id', '!=', $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Mã nhân viên này đã tồn tại trên hệ thống.' : 'Mã hợp lệ.',
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:employees,code'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'code.required' => 'Vui lòng nhập mã nhân viên.',
            'code.unique' => 'Mã nhân viên này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên nhân viên.',
        ]);

        $employee = Employee::create([
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thêm nhân viên thành công.',
                'data' => $employee,
            ], 201);
        }

        return redirect()->route('settings.employees.index')
            ->with('success', "Đã thêm nhân viên {$employee->name} ({$employee->code}) thành công.");
    }

    /**
     * Display the specified employee details (for modal edit).
     */
    public function show(Employee $employee): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('employees', 'code')->ignore($employee->id)],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'code.required' => 'Vui lòng nhập mã nhân viên.',
            'code.unique' => 'Mã nhân viên này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên nhân viên.',
        ]);

        $employee->update([
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật nhân viên thành công.',
                'data' => $employee,
            ]);
        }

        return redirect()->route('settings.employees.index')
            ->with('success', "Đã cập nhật thông tin nhân viên {$employee->name} ({$employee->code}) thành công.");
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $code = $employee->code;
        $name = $employee->name;

        $employee->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Xoá nhân viên thành công.',
            ]);
        }

        return redirect()->route('settings.employees.index')
            ->with('success', "Đã xoá nhân viên {$name} ({$code}) thành công.");
    }
}
