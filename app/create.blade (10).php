@extends('layouts.app')
@section('page-title', 'Editar Usuário')
@section('breadcrumb', 'Atualizar dados do funcionário')
@section('content')
<div style="max-width:520px; margin:0 auto">
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-user-edit"></i> Editar Usuário</div>
    </div>
    <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
        @csrf @method('PUT')
        <div class="form-group">
            <label>Nome completo</label>
            <input type="text" name="name" class="form-control {{ $errors->has('name')?'is-invalid':'' }}"
                   value="{{ old('name', $usuario->name) }}" required
                   oninput="this.value=this.value.replace(/[^a-zA-ZÀ-ÿ\s]/g,'')">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" class="form-control {{ $errors->has('email')?'is-invalid':'' }}"
                   value="{{ old('email', $usuario->email) }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Cargo</label>
            <select name="role" class="form-select" required>
                <option value="garcom"  {{ $usuario->role=='garcom' ?'selected':'' }}>🍽️ Garçom</option>
                <option value="chef"    {{ $usuario->role=='chef'   ?'selected':'' }}>👨‍🍳 Chef</option>
                <option value="caixa"   {{ $usuario->role=='caixa'  ?'selected':'' }}>💰 Caixa</option>
                <option value="gerente" {{ $usuario->role=='gerente'?'selected':'' }}>👑 Gerente</option>
            </select>
        </div>
        <div style="border-top:1px solid var(--border); padding-top:16px; margin-top:4px">
            <div style="font-size:12px; color:var(--muted); margin-bottom:12px">
                <i class="fas fa-lock"></i> Deixe em branco para manter a senha atual
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nova Senha</label>
                    <input type="password" name="password" class="form-control" placeholder="Nova senha (opcional)">
                </div>
                <div class="form-group">
                    <label>Confirmar</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repita">
                </div>
            </div>
        </div>
        <div style="display:flex; gap:10px; margin-top:8px">
            <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center">
                <i class="fas fa-save"></i> Salvar
            </button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
</div>
@endsection
