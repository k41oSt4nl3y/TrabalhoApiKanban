<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\MoveHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CardController extends Controller
{
    public function show(Card $card): JsonResponse
    {
        $card->load([
            'board:id,title',
            'column:id,name',
            'creator:id,name',
            'moveHistories' => function ($query) {
                $query->with(['fromColumn:id,name', 'toColumn:id,name', 'user:id,name'])
                      ->orderBy('at', 'desc')
                      ->limit(10);
            }
        ]);

        $cardData = [
            'id' => $card->id,
            'title' => $card->title,
            'description' => $card->description,
            'position' => $card->position,
            'board' => [
                'id' => $card->board->id,
                'title' => $card->board->title,
            ],
            'column' => [
                'id' => $card->column->id,
                'name' => $card->column->name,
            ],
            'created_by' => [
                'id' => $card->creator->id,
                'name' => $card->creator->name,
            ],
            'history' => $card->moveHistories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'type' => $history->type,
                    'from_column' => $history->fromColumn ? [
                        'id' => $history->fromColumn->id,
                        'name' => $history->fromColumn->name,
                    ] : null,
                    'to_column' => $history->toColumn ? [
                        'id' => $history->toColumn->id,
                        'name' => $history->toColumn->name,
                    ] : null,
                    'user' => [
                        'id' => $history->user->id,
                        'name' => $history->user->name,
                    ],
                    'at' => $history->at,
                ];
            }),
            'created_at' => $card->created_at,
            'updated_at' => $card->updated_at,
        ];

        return response()->json([
            'card' => $cardData
        ], 200);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1|max:120',
            'description' => 'nullable|string|max:1000',
            'column_id' => 'required|exists:columns,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Título é obrigatório (1-120 caracteres) e coluna deve existir',
                'details' => $validator->errors()
            ], 400);
        }

        $column = Column::find($request->column_id);

        if ($column->board_id !== $board->id) {
            return response()->json([
                'error' => 'Coluna inválida',
                'message' => 'A coluna especificada não pertence a este board'
            ], 400);
        }

        if ($column->isAtWipLimit()) {
            return response()->json([
                'error' => 'WIP_LIMIT_REACHED',
                'message' => "A coluna '{$column->name}' atingiu o limite de WIP ({$column->wip_limit})"
            ], 422);
        }

        $user = $request->user();

        try {
            DB::beginTransaction();

            $nextPosition = $column->cards()->max('position') + 1;

            $card = Card::create([
                'board_id' => $board->id,
                'column_id' => $column->id,
                'title' => $request->title,
                'description' => $request->description,
                'position' => $nextPosition,
                'created_by' => $user->id,
            ]);

            MoveHistory::logCreated($card, $user);

            DB::commit();

            $card->load(['column:id,name', 'creator:id,name']);

            return response()->json([
                'message' => 'Card criado com sucesso',
                'card' => [
                    'id' => $card->id,
                    'title' => $card->title,
                    'description' => $card->description,
                    'position' => $card->position,
                    'column' => [
                        'id' => $card->column->id,
                        'name' => $card->column->name,
                    ],
                    'created_by' => [
                        'id' => $card->creator->id,
                        'name' => $card->creator->name,
                    ],
                    'created_at' => $card->created_at,
                    'updated_at' => $card->updated_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível criar o card'
            ], 500);
        }
    }

    public function update(Request $request, Card $card): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|min:1|max:120',
            'description' => 'nullable|string|max:1000',
            'column_id' => 'sometimes|required|exists:columns,id',
            'position' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Dados fornecidos são inválidos',
                'details' => $validator->errors()
            ], 400);
        }

        $user = $request->user();
        $oldColumnId = $card->column_id;
        $newColumnId = $request->column_id ?? $card->column_id;

        if ($newColumnId !== $oldColumnId) {
            $newColumn = Column::find($newColumnId);

            if ($newColumn->board_id !== $card->board_id) {
                return response()->json([
                    'error' => 'Coluna inválida',
                    'message' => 'A coluna de destino deve pertencer ao mesmo board'
                ], 400);
            }

            if ($newColumn->isAtWipLimit()) {
                return response()->json([
                    'error' => 'WIP_LIMIT_REACHED',
                    'message' => "A coluna '{$newColumn->name}' atingiu o limite de WIP ({$newColumn->wip_limit})"
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $updateData = [];

            if ($request->has('title')) {
                $updateData['title'] = $request->title;
            }

            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }

            if ($request->has('column_id') && $newColumnId !== $oldColumnId) {
                $updateData['column_id'] = $newColumnId;
                
                if ($request->has('position')) {
                    $updateData['position'] = $request->position;
                } else {
                    $updateData['position'] = $newColumn->cards()->max('position') + 1;
                }
            } elseif ($request->has('position')) {
                $updateData['position'] = $request->position;
            }

            $card->update($updateData);

            if ($newColumnId !== $oldColumnId) {
                MoveHistory::logMoved($card, $oldColumnId, $newColumnId, $user);
            } else {
                MoveHistory::logUpdated($card, $user);
            }

            DB::commit();

            $card->load(['column:id,name', 'creator:id,name']);

            return response()->json([
                'message' => 'Card atualizado com sucesso',
                'card' => [
                    'id' => $card->id,
                    'title' => $card->title,
                    'description' => $card->description,
                    'position' => $card->position,
                    'column' => [
                        'id' => $card->column->id,
                        'name' => $card->column->name,
                    ],
                    'created_by' => [
                        'id' => $card->creator->id,
                        'name' => $card->creator->name,
                    ],
                    'created_at' => $card->created_at,
                    'updated_at' => $card->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível atualizar o card'
            ], 500);
        }
    }

    public function destroy(Request $request, Card $card): JsonResponse
    {
        $user = $request->user();

        try {
            DB::beginTransaction();

            MoveHistory::logDeleted($card, $user);

            $card->delete();

            DB::commit();

            return response()->json([
                'message' => 'Card deletado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível deletar o card'
            ], 500);
        }
    }
}