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
            @foreach (\App\Models\User::ROLES as $valor => $texto)
                <option value="{{ $valor }}"
                        @selected(old('role', $usuario->role ?? \App\Models\User::ROLE_ESTUDIANTE) === $valor)>
                    {{ $texto }}
                </option>
            @endforeach
        </select>
        <span class="hb-hint">
            Estudiante: busca y reseña. Arrendador: además publica.
            Administrador: modera todo. Lo normal es que un arrendador llegue
            por la solicitud verificada, no por aquí.
        </span>
        @error('role') <span class="hb-error">{{ $message }}</span> @enderror
    </div>
</div>

@if (!$usuario)
    {{-- Solo al crear. Al editar no se ofrece: un administrador no debe poder
         fijar la contraseña de otra persona. Ver el enlace de restablecimiento. --}}
    <div class="hb-fields-2">
        <div class="hb-field">
            <label class="hb-label" for="password">Contraseña inicial <span class="hb-req">*</span></label>
            <input class="hb-input @error('password') is-invalid @enderror" type="password" id="password"
                   name="password" autocomplete="new-password" required>
            <span class="hb-hint">Mínimo 8 caracteres, con letras y números. Pídele que la cambie al entrar.</span>
            @error('password') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="password_confirmation">Repetir contraseña</label>
            <input class="hb-input" type="password" id="password_confirmation" name="password_confirmation"
                   autocomplete="new-password" required>
        </div>
    </div>
@else
    <div class="hb-alert hb-alert--info">
        <span class="hb-alert__icon">i</span>
        <div>
            <strong>La contraseña no se puede cambiar desde aquí.</strong>
            Nadie, ni un administrador, debe poder fijar la clave de otra persona.
            Si este usuario perdió el acceso, genera un enlace de restablecimiento
            con el botón que hay al final de la página: solo funciona para él.
        </div>
    </div>
@endif
