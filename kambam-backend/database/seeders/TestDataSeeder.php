<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Board;
use App\Models\Column;
use App\Models\Card;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'João Silva',
            'email' => 'joao@teste.com',
            'password' => Hash::make('123456'),
        ]);

        $board = Board::create([
            'title' => 'Projeto Kanban',
            'description' => 'Board para gerenciar tarefas do projeto',
            'owner_id' => $user->id,
        ]);
        $todoColumn = Column::create([
            'board_id' => $board->id,
            'name' => 'To Do',
            'order' => 1,
            'wip_limit' => 999,
        ]);

        $doingColumn = Column::create([
            'board_id' => $board->id,
            'name' => 'Doing',
            'order' => 2,
            'wip_limit' => 3,
        ]);

        $doneColumn = Column::create([
            'board_id' => $board->id,
            'name' => 'Done',
            'order' => 3,
            'wip_limit' => 999,
        ]);
        Card::create([
            'board_id' => $board->id,
            'column_id' => $todoColumn->id,
            'title' => 'Implementar autenticação',
            'description' => 'Criar sistema de login com tokens customizados',
            'position' => 1,
            'created_by' => $user->id,
        ]);

        Card::create([
            'board_id' => $board->id,
            'column_id' => $todoColumn->id,
            'title' => 'Criar CRUD de boards',
            'description' => 'Implementar endpoints para gerenciar boards',
            'position' => 2,
            'created_by' => $user->id,
        ]);

        Card::create([
            'board_id' => $board->id,
            'column_id' => $doingColumn->id,
            'title' => 'Implementar validação WIP',
            'description' => 'Adicionar validação de limite de WIP nas colunas',
            'position' => 1,
            'created_by' => $user->id,
        ]);

        Card::create([
            'board_id' => $board->id,
            'column_id' => $doneColumn->id,
            'title' => 'Configurar banco de dados',
            'description' => 'Criar migrations e seeders',
            'position' => 1,
            'created_by' => $user->id,
        ]);
        $user2 = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@teste.com',
            'password' => Hash::make('123456'),
        ]);
        $board2 = Board::create([
            'title' => 'Projeto Frontend',
            'description' => 'Board para desenvolvimento do frontend',
            'owner_id' => $user2->id,
        ]);
        Column::create([
            'board_id' => $board2->id,
            'name' => 'To Do',
            'order' => 1,
            'wip_limit' => 999,
        ]);

        Column::create([
            'board_id' => $board2->id,
            'name' => 'Doing',
            'order' => 2,
            'wip_limit' => 2,
        ]);

        Column::create([
            'board_id' => $board2->id,
            'name' => 'Done',
            'order' => 3,
            'wip_limit' => 999,
        ]);
    }
}
