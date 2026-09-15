{{-- Campos compartidos entre crear y editar usuario. Espera $usuario (User|null). --}}
@php $usuario = $usuario ?? null; @endphp

<div class="hb-fields-2">
    <div class="hb-field">
        <label class="hb-label" for="name">Nombre <span class="hb-req">*</span></label>
        <input class="hb-input @error('name') is-invalid @enderror" type="text" id="name" name="name"
               value="{{ old('name', $usuario->name ?? '') }}" required maxlength="120">
        @error('name') <span class="hb-error">{{ $message }}</span> @enderror
    </div>

    <div class="hb-field">
        <label class="hb-label" for="email">Correo electrónico <span class="hb-req">*</span></label>
        <input class="hb-input @error('email') is-invalid @enderror" type="email" id="email" name="email"
               value="{{ old('email', $usuario->email ?? '') }}" required maxlength="180">
        @error('email') <span class="hb-error">{{ $message }}</span> @enderror
    </div>
</div>

<div class="hb-fields-2">
    <div class="hb-field">
        <label class="hb-label" for="phone">Teléfono</label>
        <input class="hb-input @error('phone') is-invalid @enderror" type="tel" id="phone" name="phone"
               value="{{ old('phone', $usuario->phone ?? '') }}" maxlength="40">
        @error('phone') <span class="hb-error">{{ $message }}</span> @enderror
    </div>

    <div class="hb-field">
        <label class="hb-label" for="role">Rol <span class="hb-req">*</span></label>
        <select class="hb-select @error('role') is-invalid @enderror" id="role" name="role" required>
            <option value="user"  @selected(old('role', $usuario->role ?? 'user') === 'user')>Usuario</option>
            <option value="admin" @selected(old('role', $usuario->role ?? '') === 'admin')>Administrador</option>
        </select>
        <span class="hb-hint">Los administradores pueden moderar publicaciones y gestionar usuarios.</span>
        @error('role') <span class="hb-error">{{ $message }}</span> @enderror
    </div>
</div>

<div class="hb-fields-2">
    <div class="hb-field">
        <label class="hb-label" for="password">
            Contraseña @if (!$usuario) <span class="hb-req">*</span> @endif
        </label>
        <input class="hb-input @error('password') is-invalid @enderror" type="password" id="password"
               name="password" autocomplete="new-password" @required(!$usuario)>
        <span class="hb-hint">
            {{ $usuario ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres, con letras y números.' }}
        </span>
        @error('password') <span class="hb-error">{{ $message }}</span> @enderror
    </div>

    <div class="hb-field">
        <label class="hb-label" for="password_confirmation">Repetir contraseña</label>
        <input class="hb-input" type="password" id="password_confirmation" name="password_confirmation"
               autocomplete="new-password" @required(!$usuario)>
    </div>
</div>
