@extends('layouts.app', ['navTransparente' => true])

@section('titulo', 'Vivienda estudiantil')
@section('descripcion', 'Encuentra habitaciones, apartaestudios y apartamentos cerca de tu universidad. Reseñas reales, filtros por precio y contacto directo con el arrendador.')

@section('contenido_completo')

    {{-- ===== Hero ===== --}}
    <section class="hb-hero">
        <div class="hb-container">
            <h1>Encuentra tu alojamiento ideal en tu ciudad</h1>
            <p class="hb-hero__lead">
                Explora alojamientos verificados: filtra por precio, servicios y barrio,
                revisa opiniones de otros estudiantes y contacta al arrendador en un clic.
            </p>

            <form class="hb-searchbar" method="GET" action="{{ route('alojamientos.index') }}" role="search">
                <label class="hb-sr-only" for="heroQ">Ciudad o barrio</label>
                <input id="heroQ" type="search" name="q" placeholder="¿En qué ciudad o barrio buscas?">

                <label class="hb-sr-only" for="heroType">Tipo de alojamiento</label>
                <select id="heroType" name="type">
                    <option value="">Cualquier tipo</option>
                    @foreach (\App\Models\Alojamiento::TIPOS as $tipo)
                        <option value="{{ $tipo }}">{{ $tipo }}</option>
                    @endforeach
                </select>

                <button class="hb-btn hb-btn--primary" type="submit">Buscar</button>
            </form>
        </div>
    </section>

    {{-- ===== Ventajas ===== --}}
    <section class="hb-section">
        <div class="hb-container">
            <div class="hb-section__head">
                <span class="hb-eyebrow">Qué ofrece HABBI</span>
                <h2>Todo lo que necesitas para decidir bien</h2>
                <p>Nada de anuncios sin información. Cada publicación trae fotos, precio claro, comodidades y opiniones de quienes ya vivieron ahí.</p>
            </div>

            <div class="hb-features">
                <article class="hb-feature">
                    <div class="hb-feature__icon" aria-hidden="true">⌕</div>
                    <h3>Filtros que sirven</h3>
                    <p>Precio, tipo, número de huéspedes y comodidades concretas: wifi, lavadora, parqueadero o servicios incluidos.</p>
                </article>

                <article class="hb-feature">
                    <div class="hb-feature__icon" aria-hidden="true">★</div>
                    <h3>Reseñas reales</h3>
                    <p>Solo quien tiene cuenta puede calificar, y nadie puede reseñar su propia publicación. Una opinión por persona.</p>
                </article>

                <article class="hb-feature">
                    <div class="hb-feature__icon" aria-hidden="true">✆</div>
                    <h3>Contacto directo</h3>
                    <p>WhatsApp o llamada al arrendador desde la ficha. Sin intermediarios y sin comisiones.</p>
                </article>

                <article class="hb-feature">
                    <div class="hb-feature__icon" aria-hidden="true">＋</div>
                    <h3>Publicar es gratis</h3>
                    <p>Sube hasta 8 fotos, define el precio por mes o por noche y administra tus publicaciones cuando quieras.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ===== Destacados ===== --}}
    @if ($destacados->isNotEmpty())
        <section class="hb-section hb-section--alt">
            <div class="hb-container">
                <div class="hb-section__head">
                    <span class="hb-eyebrow">Mejor valorados</span>
                    <h2>Alojamientos destacados</h2>
                    <p>Los que más gustan a la comunidad de estudiantes.</p>
                </div>

                <div class="hb-grid">
                    @foreach ($destacados as $a)
                        @include('partials.listing-card', ['a' => $a])
                    @endforeach
                </div>

                <p class="hb-center" style="margin-top:2.5rem">
                    <a class="hb-btn hb-btn--ghost" href="{{ route('alojamientos.index') }}">Ver todos los alojamientos</a>
                </p>
            </div>
        </section>
    @endif

    {{-- ===== Cómo funciona ===== --}}
    <section class="hb-section" id="como-funciona">
        <div class="hb-container">
            <div class="hb-section__head">
                <span class="hb-eyebrow">Cómo funciona</span>
                <h2>Tres pasos y listo</h2>
            </div>

            <div class="hb-steps">
                <article class="hb-step">
                    <h3>Busca y filtra</h3>
                    <p>Escribe tu ciudad o barrio y ajusta precio, tipo y comodidades hasta quedarte con lo que de verdad te sirve.</p>
                </article>
                <article class="hb-step">
                    <h3>Compara con reseñas</h3>
                    <p>Mira las fotos, lee las opiniones de otros estudiantes y revisa la calificación promedio.</p>
                </article>
                <article class="hb-step">
                    <h3>Contacta al arrendador</h3>
                    <p>Escríbele por WhatsApp o llámalo directamente. Tú negocias, sin intermediarios.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ===== CTA ===== --}}
    <section class="hb-section hb-section--alt">
        <div class="hb-container">
            <div class="hb-cta">
                <h2>¿Tienes una habitación o apartamento libre?</h2>
                <p>Publícalo gratis en HABBI y llega a estudiantes que buscan exactamente lo que ofreces.</p>
                <a class="hb-btn hb-btn--white" href="{{ route('alojamientos.create') }}">Publicar mi alojamiento</a>
            </div>
        </div>
    </section>

@endsection
