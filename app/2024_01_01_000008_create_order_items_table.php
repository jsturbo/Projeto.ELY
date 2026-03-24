<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateTestUsers extends Command
{
    protected $signature = 'app:create-test-users';
    protected $description = 'Cria usuários de teste para o dashboard';

    public function handle()
    {
        // Verificar se usuários já existem
        if (User::count() > 0) {
            $this->info('⚠️ Usuários já existem no banco de dados!');
            return;
        }

        $users = [
            [
                'name' => 'Gerente João',
                'email' => 'gerente@restaurante.com',
                'password' => bcrypt('password'),
                'role' => 'gerente',
                'ativo' => true,
            ],
            [
                'name' => 'João Garçom',
                'email' => 'garcom@restaurante.com',
                'password' => bcrypt('password'),
                'role' => 'garcom',
                'ativo' => true,
            ],
            [
                'name' => 'Chef Pedro',
                'email' => 'chef@restaurante.com',
                'password' => bcrypt('password'),
                'role' => 'chef',
                'ativo' => true,
            ],
            [
                'name' => 'Maria Caixa',
                'email' => 'caixa@restaurante.com',
                'password' => bcrypt('password'),
                'role' => 'caixa',
                'ativo' => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
            $this->info("✅ Usuário criado: {$user['email']} ({$user['role']})");
        }

        $this->info("\n✨ Todos os usuários foram criados com sucesso!");
        $this->info("\n📝 Credenciais de teste:");
        $this->line("   Cargo: gerente | Senha: password");
        $this->line("   Cargo: garcom | Senha: password");
        $this->line("   Cargo: chef | Senha: password");
        $this->line("   Cargo: caixa | Senha: password");
    }
}
