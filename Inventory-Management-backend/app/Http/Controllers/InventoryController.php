<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;


class InventoryController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $cacheKey = "inventory_page_{$page}_perPage_{$perPage}_search_" . md5($search);

        $inventory = Cache::remember($cacheKey, 60, function () use ($perPage, $page, $search) {
            $query = Inventory::with('creator:id,name')->orderBy('created_at', 'desc');
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
            return $query->paginate($perPage, ['*'], 'page', $page);
        });

        return response()->json($inventory);
    }



    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = Inventory::create([
            'name' => $request->name,
            'description' => $request->description,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'created_by' => $user->id,
        ]);

        // Log the action
        AuditLog::create([
            'action' => 'Item created',
            'user_id' => $user->id,
            'details' => "Created item: {$item->name} (ID: {$item->id})"
        ]);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = Inventory::with('creator')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item = Inventory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'sometimes|required|integer|min:0',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->update($request->all());

        // Log the action
        AuditLog::create([
            'action' => 'Item updated',
            'user_id' => $user->id,
            'details' => "Updated item: {$item->name} (ID: {$item->id})"
        ]);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item = Inventory::findOrFail($id);
        $item->delete();

        // Log the action
        AuditLog::create([
            'action' => 'Item deleted',
            'user_id' => $user->id,
            'details' => "Deleted item: {$item->name} (ID: {$item->id})"
        ]);

        return response()->json(['message' => 'Item deleted successfully']);
    }
}