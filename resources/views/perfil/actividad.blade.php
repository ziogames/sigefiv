<x-card
    title="Actividad de la Cuenta"
    icon="cil-history"
    class="mt-4">

    <x-table bordered>

        <tbody>

            <tr>
                <th width="260">ID del Usuario</th>
                <td>{{ $usuario->id }}</td>
            </tr>

            <tr>
                <th>Estado</th>
                <td>
                    <span class="badge bg-success">
                        Activo
                    </span>
                </td>
            </tr>

            <tr>
                <th>Registrado</th>
                <td>{{ $usuario->created_at->format('d/m/Y H:i') }}</td>
            </tr>

            <tr>
                <th>Última actualización</th>
                <td>{{ $usuario->updated_at->format('d/m/Y H:i') }}</td>
            </tr>

            <tr>
                <th>Correo verificado</th>
                <td>
                    @if($usuario->email_verified_at)

                        <span class="badge bg-success">
                            Verificado
                        </span>

                    @else

                        <span class="badge bg-warning">
                            No verificado
                        </span>

                    @endif
                </td>
            </tr>

            <tr>
                <th>Dirección IP</th>
                <td>
                    {{ $usuario->ultima_ip ?? 'No registrada' }}
                </td>
            </tr>

            <tr>
                <th>Último acceso</th>
                <td>
                    {{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? 'Primer acceso' }}
                </td>
            </tr>

            <tr>
                <th>Navegador actual</th>
                <td style="font-size:12px">
                    {{ request()->userAgent() }}
                </td>
            </tr>

        </tbody>

    </x-table>

</x-card>