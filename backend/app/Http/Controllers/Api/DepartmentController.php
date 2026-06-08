<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        return response()->json(
            Department::query()
                ->withCount('users')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $department = Department::create($data);

        return response()->json($department, 201);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $department->update($data);

        return response()->json($department->loadCount('users'));
    }

    public function destroy(Department $department)
    {
        User::where('department_id', $department->id)->update([
            'department_id' => null,
        ]);

        $department->delete();

        return response()->noContent();
    }
}
