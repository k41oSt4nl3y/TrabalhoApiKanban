<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BoardController extends Controller
{
    public function index(): JsonResponse
    {
        $boards = Board::with(['owner:id,name', 'columns:id,board_id,name,order,wip_limit'])
            ->select('id', 'title', 'description', 'owner_id', 'created_at', 'updated_at')
            ->get()
            ->map(function ($board) {
                return [
                    'id' => $board->id,
                    'title' => $board->title,
                    'description' => $board->description,
                    'owner' => [
                        'id' => $board->owner->id,
                        'name' => $board->owner->name,
                    ],
                    'columns_count' => $board->columns->count(),
                    'cards_count' => $board->cards()->count(),
                    'created_at' => $board->created_at,
                    'updated_at' => $board->updated_at,
                ];
            });

        return response()->json([
            'boards' => $boards
        ], 200);
    }

    public function show(Board $board): JsonResponse
    {
        $board->load([
            'owner:id,name',
            'columns' => function ($query) {
                $query->orderBy('order');
            },
            'columns.cards' => function ($query) {
                $query->orderBy('position');
            },
            'columns.cards.creator:id,name'
        ]);

        $boardData = [
            'id' => $board->id,
            'title' => $board->title,
            'description' => $board->description,
            'owner' => [
                'id' => $board->owner->id,
                'name' => $board->owner->name,
            ],
            'columns' => $board->columns->map(function ($column) {
                return [
                    'id' => $column->id,
                    'name' => $column->name,
                    'order' => $column->order,
                    'wip_limit' => $column->wip_limit,
                    'cards_count' => $column->cards->count(),
                    'cards' => $column->cards->map(function ($card) {
                        return [
                            'id' => $card->id,
                            'title' => $card->title,
                            'description' => $card->description,
                            'position' => $card->position,
                            'created_by' => [
                                'id' => $card->creator->id,
                                'name' => $card->creator->name,
                            ],
                            'created_at' => $card->created_at,
                            'updated_at' => $card->updated_at,
                        ];
                    }),
                ];
            }),
            'created_at' => $board->created_at,
            'updated_at' => $board->updated_at,
        ];

        return response()->json([
            'board' => $boardData
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1|max:80',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Título é obrigatório (1-80 caracteres)',
                'details' => $validator->errors()
            ], 400);
        }

        $user = $request->user();

        try {
            DB::beginTransaction();

            $board = Board::create([
                'title' => $request->title,
                'description' => $request->description,
                'owner_id' => $user->id,
            ]);

            // Criar colunas padrão
            $defaultColumns = [
                ['name' => 'To Do', 'order' => 1, 'wip_limit' => 999],
                ['name' => 'Doing', 'order' => 2, 'wip_limit' => 999],
                ['name' => 'Done', 'order' => 3, 'wip_limit' => 999],
            ];

            foreach ($defaultColumns as $columnData) {
                Column::create([
                    'board_id' => $board->id,
                    'name' => $columnData['name'],
                    'order' => $columnData['order'],
                    'wip_limit' => $columnData['wip_limit'],
                ]);
            }

            DB::commit();

            $board->load(['owner:id,name', 'columns:id,board_id,name,order,wip_limit']);

            return response()->json([
                'message' => 'Board criado com sucesso',
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'description' => $board->description,
                    'owner' => [
                        'id' => $board->owner->id,
                        'name' => $board->owner->name,
                    ],
                    'columns' => $board->columns->map(function ($column) {
                        return [
                            'id' => $column->id,
                            'name' => $column->name,
                            'order' => $column->order,
                            'wip_limit' => $column->wip_limit,
                        ];
                    }),
                    'created_at' => $board->created_at,
                    'updated_at' => $board->updated_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível criar o board'
            ], 500);
        }
    }

    public function update(Request $request, Board $board): JsonResponse
    {
        $user = $request->user();

        if (!$board->isOwnedBy($user)) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o proprietário pode editar este board'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1|max:80',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => 'Título é obrigatório (1-80 caracteres)',
                'details' => $validator->errors()
            ], 400);
        }

        $board->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        $board->load(['owner:id,name']);

        return response()->json([
            'message' => 'Board atualizado com sucesso',
            'board' => [
                'id' => $board->id,
                'title' => $board->title,
                'description' => $board->description,
                'owner' => [
                    'id' => $board->owner->id,
                    'name' => $board->owner->name,
                ],
                'created_at' => $board->created_at,
                'updated_at' => $board->updated_at,
            ]
        ], 200);
    }

    public function destroy(Request $request, Board $board): JsonResponse
    {
        $user = $request->user();

        if (!$board->isOwnedBy($user)) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o proprietário pode deletar este board'
            ], 403);
        }

        $board->delete();

        return response()->json([
            'message' => 'Board deletado com sucesso'
        ], 200);
    }
}