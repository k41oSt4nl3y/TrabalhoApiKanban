<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function show(Card $card)
    {
        return response()->json($card->load('column'));
    }

    public function store(Request $request, Board $board)
    {
        $this->authorize('createCard', $board);
        
        $validated = $request->validate([
            'title' => 'required|string|min:1|max:120',
            'description' => 'nullable|string',
            'column_id' => 'required|exists:columns,id',
            'position' => 'nullable|integer'
        ]);

        $column = Column::findOrFail($validated['column_id']);
        
        // Verify column belongs to board
        if ($column->board_id !== $board->id) {
            abort(422, 'Column does not belong to this board');
        }

        // Check WIP limit
        if ($column->wip_limit && $column->cards()->count() >= $column->wip_limit) {
            throw ValidationException::withMessages([
                'column_id' => ['WIP_LIMIT_REACHED']
            ]);
        }

        $card = $board->cards()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'column_id' => $validated['column_id'],
            'position' => $validated['position'] ?? 0,
            'created_by' => $request->user()->id
        ]);

        return response()->json($card, 201);
    }

    public function update(Request $request, Card $card)
    {
        $this->authorize('update', $card->board);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|min:1|max:120',
            'description' => 'nullable|string',
            'column_id' => 'sometimes|required|exists:columns,id',
            'position' => 'sometimes|required|integer'
        ]);

        if (isset($validated['column_id'])) {
            $newColumn = Column::findOrFail($validated['column_id']);
            
            // Verify column belongs to same board
            if ($newColumn->board_id !== $card->board_id) {
                abort(422, 'Cannot move card to a different board');
            }

            // Check WIP limit only if moving to a different column
            if ($newColumn->id !== $card->column_id) {
                if ($newColumn->wip_limit && $newColumn->cards()->count() >= $newColumn->wip_limit) {
                    throw ValidationException::withMessages([
                        'column_id' => ['WIP_LIMIT_REACHED']
                    ]);
                }
            }
        }

        $card->update($validated);
        return response()->json($card);
    }

    public function destroy(Card $card)
    {
        $this->authorize('update', $card->board);
        $card->delete();
        return response()->json(null, 204);
    }
}
