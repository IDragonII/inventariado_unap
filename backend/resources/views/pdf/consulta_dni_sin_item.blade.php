<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Activos sin Item - UNA PUNO</title>
    <style>
        @page { margin: 3cm 1cm 5.5cm 1cm; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        #page-header {
            position: fixed;
            top: -2.5cm;
            left: 0; right: 0;
            height: 2.2cm;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }
        .header-inner { display: table; width: 100%; }
        .logo-box {
            display: table-cell;
            width: 65px;
            vertical-align: middle;
            padding-right: 10px;
        }
        .logo-box img { width: 55px; display: block; }
        .logo-placeholder {
            width: 55px; height: 55px;
            border: 1px solid #aaa;
            text-align: center; font-size: 7px;
            color: #888; line-height: 1.3;
            padding-top: 18px; box-sizing: border-box;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            font-family: "Palatino Linotype", Palatino, serif;
            font-style: italic;
            font-size: 13px;
            line-height: 1.5;
        }

        /* FOOTER */
        #page-footer {
            position: fixed;
            bottom: -6.2cm;
            left: 0; right: 0;
            height: 4.6cm;
            border-top: 2px solid #000;
            padding-top: 6px;
        }
        .legal-text {
            text-align: justify;
            font-style: italic;
            font-size: 10px;
            line-height: 1.4;
            margin-bottom: 4px;
        }
        .estado-hint { font-size: 9px; margin-bottom: 8px; }
        .fecha-centro { text-align: center; font-size: 11px; margin-top: 6px; }
        .signature-table { width: 100%; border-collapse: collapse; }
        .sig-box { text-align: center; width: 50%; padding-top: 4px; }
        .sig-line { border-top: 1px solid #000; width: 70%; margin: 0 auto 4px; }

        /* CONTENIDO */
        .title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 15px 0;
            text-transform: uppercase;
        }

        .page-header-info {
            margin-bottom: 10px;
            page-break-after: avoid;
        }
        .page-header-info p {
            margin: 2px 0;
        }
        .info-label { font-weight: bold; }

        .bienes-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        
        .bienes-table thead tr {
            border-bottom: 2px solid #000;
        }
        
        .bienes-table th {
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #000;
        }
        
        .bienes-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #000;
            text-align: left;
        }
        
        .bienes-table tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .footer-total {
            margin-top: 15px;
            text-align: right;
            font-size: 10px;
        }

        .area-section {
            page-break-after: always;
        }
        .area-section:last-child {
            page-break-after: auto;
        }
    </style>
</head>
<body>

    @include('pdf.partials.headerItem')

    @include('pdf.partials.footerItem')

    {{-- CONTENIDO PRINCIPAL --}}
    @php
        $activosPorArea = $activos->groupBy('area.aula');
        $contadorGlobal = 1;
    @endphp

    @foreach($activosPorArea as $areaNombre => $areaActivos)
    <div class="area-section">
        <div class="title">CARGO PERSONAL DE BIENES PATRIMONIALES</div>
        <div class="page-header-info">
            <p><span class="info-label">Oficina:</span> {{ $oficina }}</p>
            <p><span class="info-label">Nombre y Apellido:</span> {{ strtoupper($user->name) }} - {{ $user->dni }}</p>
            <p><span class="info-label">Área:</span> {{ $areaNombre }}</p>
        </div>
        <table class="bienes-table">
            <thead>
                <tr>
                    <th style="width:5%">N°</th>
                    <th style="width:10%">Código</th>
                    <th style="width:18%">Denominación</th>
                    <th style="width:8%">Marca</th>
                    <th style="width:8%">Modelo</th>
                    <th style="width:10%">Serie</th>
                    <th style="width:31%">Descripción</th>
                    <th style="width:10%">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areaActivos as $index => $activo)
                <tr>
                    <td>{{ $contadorGlobal++ }}</td>
                    <td>{{ $activo->codigo }}</td>
                    <td>{{ $activo->denominacion }}</td>
                    <td>{{ $activo->marca ?? '-' }}</td>
                    <td>{{ $activo->modelo ?? '-' }}</td>
                    <td>{{ $activo->numero_serie ?? '-' }}</td>
                    <td>{{ $activo->descripcion ?? '-' }}</td>
                    <td>{{ strtoupper($activo->estado_conservacion ?? 'regular') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p style="text-align: right; font-size: 10px; margin-top: 5px;"><strong>Subtotal: {{ $areaActivos->count() }}</strong></p>
    </div>
    @endforeach

</body>
</html>