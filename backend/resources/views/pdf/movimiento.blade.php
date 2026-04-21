<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta de Transferencia - UNA PUNO</title>
    <style>
        @page { margin: 1cm; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
        }

        /* --- ENCABEZADO --- */
        .header-container { width: 100%; margin-bottom: 5px; }
        .logo-box { display: inline-block; width: 15%; vertical-align: middle; }
        .header-text {
            display: inline-block;
            width: 80%;
            vertical-align: middle;
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            font-style: italic;
            font-size: 13px;
        }

        .title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            text-decoration: underline;
        }

        /* --- SECCIÓN DE INFORMACIÓN (SIN BORDES, CON COLOR) --- */
        .info-table-clean {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: none;
        }
        .info-table-clean td {
            border: none;
            padding: 4px 8px;
            vertical-align: middle;
        }
        /* Color azul solo para las etiquetas */
        .label-cell {
            background-color: #ffffff; 
            font-weight: bold;
            width: 18%;
        }
        .data-cell {
            width: 32%;
            background-color: #ffffff;
        }

        /* --- SEPARADOR DOBLE --- */
        .double-line {
            border-top: 3px double #000;
            margin: 10px 0;
            width: 100%;
        }

        /* --- TABLA DE BIENES (CON BORDES) --- */
        .bienes-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bienes-table th, .bienes-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 9px;
        }
        .bienes-table th {
            background-color: #d9e1f2;
            font-weight: bold;
        }
        .text-left { text-align: left !important; }

        /* --- TEXTOS, FECHA Y FIRMAS --- */
        .legal-text {
            margin-top: 15px;
            text-align: justify;
            font-style: italic;
            font-size: 10.5px;
            line-height: 1.4;
        }
        .fecha-centro {
            text-align: center;
            margin: 25px 0;
            font-size: 11px;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
        }
        .sig-box {
            text-align: center;
            width: 50%;
        }
        .sig-line {
            border-top: 1px solid #000;
            width: 70%;
            margin: 0 auto 5px;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="logo-box">
            @if(isset($logo)) <img src="{{ $logo }}" style="width: 55px;"> @endif
        </div>
        <div class="header-text">
            Universidad Nacional del Altiplano<br>
            Unidad de abastecimiento<br>
            Sub Unidad de Patrimonio
        </div>
    </div>

    <div class="title">ACTA DE TRANSFERENCIA DE BIENES</div>

    <table class="info-table-clean">
        <tr>
            <td class="label-cell">Quien recibe:</td>
            <td class="data-cell">{{ strtoupper($movimiento->receptor->name) }}</td>
            <td class="label-cell">Quien entrega:</td>
            <td class="data-cell">{{ strtoupper($movimiento->usuario->name) }}</td>
        </tr>
        <tr>
            <td class="label-cell">DNI:</td>
            <td class="data-cell">{{ $movimiento->receptor->dni }}</td>
            <td class="label-cell">DNI:</td>
            <td class="data-cell">{{ $movimiento->usuario->dni }}</td>
        </tr>
        <tr>
            <td class="label-cell">Correo institucional:</td>
            <td class="data-cell">{{ $movimiento->receptor->email ?? '---' }}</td>
            <td class="label-cell">Correo institucional:</td>
            <td class="data-cell">{{ $movimiento->usuario->email ?? '---' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Numero de celular:</td>
            <td class="data-cell">{{ $movimiento->receptor->telefono ?? '---' }}</td>
            <td class="label-cell">Numero de celular:</td>
            <td class="data-cell">{{ $movimiento->usuario->telefono ?? '---' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Centro de costo:</td>
            <td colspan="3" class="data-cell">{{ strtoupper($movimiento->ubicacionDestino->denominacion ?? 'N/A') }}</td>
        </tr>
        <tr>
            <td class="label-cell">Observacion:</td>
            <td colspan="3" class="data-cell">{{ strtoupper($movimiento->observaciones_entrega ?? 'TRANSFERENCIA DE BIENES.') }}</td>
        </tr>
        <tr>
            <td class="label-cell">Referencia:</td>
            <td colspan="3" class="data-cell">{{ $movimiento->codigo_referencia ?? 'OFICIO N° 0xxx-20xx-ADM-CE-CxxH-FCxxxCBB-UNA-PUNO' }}</td>
        </tr>
    </table>

    <div class="double-line"></div>

    <table class="bienes-table">
        <thead>
            <tr>
                <th style="width: 5%;">?</th>
                <th style="width: 15%;">CODIGO PATRIMONIAL</th>
                <th style="width: 35%;">DESCRIPCION DEL BIEN</th>
                <th>MODELO</th>
                <th>MARCA</th>
                <th>SERIE</th>
                <th>ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimiento->movimientosActivos as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->activo->codigo }}</td>
                <td class="text-left">{{ $item->activo->denominacion }}</td>
                <td>{{ $item->activo->modelo }}</td>
                <td>{{ $item->activo->marca }}</td>
                <td>{{ $item->activo->numero_serie }}</td>
                <td>{{ strtoupper($item->activo->estado_conservacion ?? 'REGULAR') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legal-text">
        Se procedio con la descripción a detalle de los bienes a transferir, y al firmar el presente documento, quien recibe asume la responsabilidad del bien(es) que esta asumiendo esto sobre la custodia, existencia, permanencia, conservacion y funcionamiento de los bienes, por otra parte quien entrega los bienes tiene la reponsabilidad de entregar una copia de la presente acta a la sub unidad de patrimonio para que se actualice la informacion del control patrimonial. En señal de conformidad ambos suscriben la presente acta.
    </div>

    <div style="font-size: 9px; margin-top: 5px;">
        Tipos de estado nuevo (n) bueno (b) regular(r) malo(m)
    </div>

    <div class="fecha-centro">
        {{ $movimiento->fecha_movimiento->format('l, j \d\e F \d\e Y') }}
    </div>

    <table class="signature-table">
        <tr>
            <td class="sig-box">
                <div class="sig-line"></div>
                Firma<br>---Quien recibe---
            </td>
            <td class="sig-box">
                <div class="sig-line"></div>
                Firma<br>---Quien entrega---
            </td>
        </tr>
    </table>

</body>
</html>