<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ColumnController extends Controller
{
    public function store(Request $request, Board $board): JsonResponse
    {
        $user = $request->user();

        if (!$board->isOwnedBy($user)) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o proprietário pode adicionar colunas a este board'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:1|max:40',
            'wip_limit' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Nome é obrigatório (1-40 caracteres) e WIP limit deve ser >= 0',
                'details' => $validator->errors()
            ], 400);
        }

        // Calcular a próxima ordem
        $nextOrder = $board->columns()->max('order') + 1;

        $column = Column::create([
            'board_id' => $board->id,
            'name' => $request->name,
            'order' => $nextOrder,
            'wip_limit' => $request->wip_limit ?? 999,
        ]);

        return response()->json([
            'message' => 'Coluna criada com sucesso',
            'column' => [
                'id' => $column->id,
                'name' => $column->name,
                'order' => $column->order,
                'wip_limit' => $column->wip_limit,
                'cards_count' => 0,
                'created_at' => $column->created_at,
                'updated_at' => $column->updated_at,
            ]
        ], 201);
    }

    public function update(Request $request, Column $column): JsonResponse
    {
        $user = $request->user();

        if (!$column->board->isOwnedBy($user)) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o proprietário pode editar esta coluna'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:1|max:40',
            'wip_limit' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Nome é obrigatório (1-40 caracteres) e WIP limit deve ser >= 0',
                'details' => $validator->errors()
            ], 400);
        }

        // Verificar se o novo WIP limit não é menor que o número atual de cards
        $currentCardsCount = $column->getCardsCount();
        if ($request->wip_limit < $currentCardsCount) {
            return response()->json([
                'error' => 'WIP limit inválido',
                'message' => "O WIP limit não pode ser menor que o número atual de cards ({$currentCardsCount})",
                'details' => [
                    'wip_limit' => ["O WIP limit deve ser >= {$currentCardsCount}"]
                ]
            ], 422);
        }

        $column->update([
            'name' => $request->name,
            'wip_limit' => $request->wip_limit,
        ]);

        return response()->json([
            'message' => 'Coluna atualizada com sucesso',
            'column' => [
                'id' => $column->id,
                'name' => $column->name,
                'order' => $column->order,
                'wip_limit' => $column->wip_limit,
                'cards_count' => $column->getCardsCount(),
                'created_at' => $column->created_at,
                'updated_at' => $column->updated_at,
            ]
        ], 200);
    }

    public function destroy(Request $request, Column $column): JsonResponse
    {
        $user = $request->user();

        if (!$column->board->isOwnedBy($user)) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o proprietário pode deletar esta coluna'
            ], 403);
        }

        // Verificar se a coluna tem cards
        if ($column->getCardsCount() > 0) {
            return response()->json([
                'error' => 'Coluna não pode ser deletada',
                'message' => 'Não é possível deletar uma coluna que contém cards'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Reordenar as colunas restantes
            $column->board->columns()
                ->where('order', '>', $column->order)
                ->decrement('order');

            $column->delete();

            DB::commit();

            return response()->json([
                'message' => 'Coluna deletada com sucesso'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível deletar a coluna'
            ], 500);
        }
    }
}