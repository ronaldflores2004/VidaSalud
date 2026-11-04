{{-- CU21: estado del pago de la ficha. Uso: @include('compartido.partials.estado-pago', ['cita' => $c]) --}}
@if(! $cita->pago)
    @if(in_array($cita->estado, ['PROGRAMADA', 'CONFIRMADA']))<span class="chip chip-pendiente-pago">Pendiente de pago</span>@else<span class="sub">—</span>@endif
@elseif($cita->pago->estado === 'PAGADO')
    <span class="chip chip-pagado" title="{{ $cita->pago->metodo_texto }} · {{ $cita->pago->codigo }}">Pagada · {{ $cita->pago->metodo_texto }}</span>
@elseif($cita->pago->estado === 'POR_REEMBOLSAR')
    <span class="chip chip-inasistencia">Por reembolsar</span>
@else
    <span class="chip chip-cancelada">Reembolsada</span>
@endif
