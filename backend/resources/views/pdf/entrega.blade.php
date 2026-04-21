<h2>Acta de Entrega</h2>

<p><strong>Entrega:</strong> {{ $usuario['nombre'] }}</p>
<p><strong>Recibe:</strong> {{ $receptor['nombre'] }}</p>

<hr>

<table width="100%" border="1">
    <tr>
        <th>Código</th>
        <th>Denominación</th>
    </tr>

    @foreach($activos as $a)
    <tr>
        <td>{{ $a['id'] }}</td>
        <td>{{ $a['denominacion'] }}</td>
    </tr>
    @endforeach
</table>