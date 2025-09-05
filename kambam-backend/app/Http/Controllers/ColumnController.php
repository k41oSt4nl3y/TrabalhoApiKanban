<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Column;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function store(Request $request, Board $board)
    {
        $this->authorize('update', $board);
        
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:40',
            'wip_limit' => 'nullable|integer|min:0',
            'order' => 'nullable|integer'
        ]);

        $column = $board->columns()->create($validated);
        return response()->json($column, 201);
    }

    public function update(Request $request, Column $column)
    {
        $this->authorize('update', $column->board);
        
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:40',
            'wip_limit' => 'nullable|integer|min:0',
            'order' => 'nullable|integer'
        ]);

        $column->update($validated);
        return response()->json($column);
    }

    public function destroy(Column $column)
    {
        $this->authorize('delete', $column->board);
        $column->delete();
        return response()->json(null, 204);
    }
}
