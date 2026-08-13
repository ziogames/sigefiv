<h1>Mis tareas</h1>

<form action="{{ route('tareas.store') }}" method="POST">
    @csrf

    <input
        type="text"
        name="titulo"
        placeholder="Escribe una tarea"
        value="{{ old('titulo') }}"
    >

    <button type="submit">Agregar</button>
</form>

@error('titulo')
    <p style="color: red">{{ $message }}</p>
@enderror

<ul>
    @forelse ($tareas as $tarea)
        <li>
            {{ $tarea->titulo }}

            <form action="{{ route('tareas.destroy', $tarea) }}" method="POST" style="display: inline">
                @csrf
                @method('DELETE')

                <button type="submit">Eliminar</button>
            </form>
        </li>
    @empty
        <li>No hay tareas todavía.</li>
    @endforelse
</ul>