{{-- Recordatorio de verificar el correo. Se muestra en todas las páginas
     mientras falte, pero no bloquea nada: solo informa qué se desbloquea. --}}
@auth
    @if (! auth()->user()->hasVerifiedEmail())
        <div class="hb-verificar">
            <div class="hb-container hb-verificar__inner">
                <span>
                    Falta verificar tu correo. Lo necesitas para dejar reseñas,
                    reportar publicaciones y pedir ser arrendador.
                </span>
                <form method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button class="hb-btn hb-btn--white hb-btn--sm" type="submit">
                        Enviarme el enlace
                    </button>
                </form>
            </div>
        </div>
    @endif
@endauth
