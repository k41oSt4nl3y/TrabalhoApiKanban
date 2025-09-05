<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Column;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index()
    {
        $boards = Board::with('owner:id,name')
            ->select('id', 'title', 'description', 'owner_id')
            ->get();
        return response()->json($boards);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:1|max:80',
            'description' => 'nullable|string'
        ]);

        $board = $request->user()->boards()->create($validated);

        // Create default columns
        $defaultColumns = [
            ['name' => 'To Do', 'order' => 1, 'wip_limit' => 999],
            ['name' => 'Doing', 'order' => 2, 'wip_limit' => 999],
            ['name' => 'Done', 'order' => 3, 'wip_limit' => 999]
        ];

        foreach ($defaultColumns as $column) {
            $board->columns()->create($column);
        }

        return response()->json($board->load('columns'), 201);
    }

    public function show(Board $board)
    {
        return response()->json($board->load(['columns' => function($query) {
            $query->orderBy('order');
        }, 'columns.cards' => function($query) {
            $query->orderBy('position');
        }, 'owner:id,name']));
    }

    public function update(Request $request, Board $board)
    {
        $this->authorize('update', $board);
        
        $validated = $request->validate([
            'title' => 'required|string|min:1|max:80',
            'description' => 'nullable|string'
        ]);

        $board->update($validated);
        return response()->json($board);
    }

    public function destroy(Board $board)
    {
        $this->authorize('delete', $board);
        $board->delete();
        return response()->json(null, 204);
    }
}
