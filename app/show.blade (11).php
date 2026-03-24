@extends('layouts.app')
@section('page-title', 'Novo Usuário')
@section('breadcrumb', 'Cadastrar funcionário')
@section('content')
<div style="max-width:520px; margin:0 auto">
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="fas fa-user-plus"></i> Cadastrar Usuário</div>
    </div>
    <form method="POST" action="{{ route('usuarios.store') }}">
        @csrf
        <div class="form-group">
            <label>Nome completo</label>
            <input type="text" name="name" class="form-control {{ $errors->has('name')?'is-invalid':'' }}"
                   value="{{ old('name') }}" placeholder="Nome do funcionário" required
                   oninput="this.value=this.value.replace(/[^a-zA-ZÀ-ÿ\s]/g,'')">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" class="form-control {{ $errors->has('email')?'is-invalid':'' }}"
                   value="{{ old('email') }}" placeholder="email@restaurante.com" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Cargo</label>
            <select name="role" class="form-select {{ $errors->has('role')?'is-invalid':'' }}" required>
                <option value="">— Selecione —</option>
                <option value="garcom"  {{ old('role')=='garcom' ?'selected':'' }}>🍽️ Garçom</option>
                <option value="chef"    {{ old('role')=='chef'   ?'selected':'' }}>👨‍🍳 Chef</option>
                <option value="caixa"   {{ old('role')=='caixa'  ?'selected':'' }}>💰 Caixa</option>
                <option value="gerente" {{ old('role')=='gerente'?'selected':'' }}>👑 Gerente</option>
            </select>
            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" class="form-control {{ $errors->has('password')?'is-invalid':'' }}"
                       placeholder="Mínimo 6 caracteres" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Repita a senha" required>
            </div>
        </div>
        <div style="display:flex; gap:10px; margin-top:8px">
            <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center">
                <i class="fas fa-save"></i> Cadastrar
            </button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
</div>
@endsection
