<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'role'     => 'required|in:gerente,garcom,chef,caixa',
            'password' => 'required|string',
        ], [
            'role.required'     => 'Selecione um cargo.',
            'role.in'           => 'Cargo inválido.',
            'password.required' => 'Digite sua senha.',
        ]);

        // Busca todos os usuários ativos com esse cargo
        $usuarios = User::where('role', $validated['role'])
            ->where('ativo', true)
            ->get();

        if ($usuarios->isEmpty()) {
            return back()->withErrors(['password' => 'Nenhum usuário ativo encontrado para este cargo.'])->onlyInput('role');
        }

        // Verifica qual usuário tem essa senha
        $user = null;
        foreach ($usuarios as $u) {
            if (Hash::check($validated['password'], $u->password)) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            return back()->withErrors(['password' => 'Senha incorreta.'])->onlyInput('role');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
    public function usuariosPorCargo(Request $request)
    {
        $role = $request->query('role');
        if (!$role) return response()->json([]);
        $usuarios = User::where('role', $role)->where('ativo', true)
            ->select('id','name')->get();
        return response()->json($usuarios);
    }

}
