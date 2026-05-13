<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Acta de Transferencia - UNA PUNO</title>

    <style>

        @page {
            size: A4 portrait;
            margin: 2.8cm 1cm 5cm 1cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }

        /* =========================================================
           HEADER
        ========================================================== */

        #page-header {
            position: fixed;
            top: -2.5cm;
            left: 0;
            right: 0;
            height: 2.2cm;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }

        .header-inner {
            display: table;
            width: 100%;
        }

        .logo-box {
            display: table-cell;
            width: 65px;
            vertical-align: middle;
            padding-right: 10px;
        }

        .logo-box img {
            width: 55px;
            display: block;
        }

        .logo-placeholder {
            width: 55px;
            height: 55px;
            border: 1px solid #aaa;
            text-align: center;
            font-size: 7px;
            color: #888;
            line-height: 1.3;
            padding-top: 18px;
            box-sizing: border-box;
        }

        .header-text {
            display: table-cell;
            vertical-align: middle;
            font-family: "Palatino Linotype", Palatino, serif;
            font-style: italic;
            font-size: 12px;
            line-height: 1.4;
        }

        /* =========================================================
           FOOTER
        ========================================================== */

        #page-footer {
            position: fixed;
            bottom: -6cm;
            left: 0;
            right: 0;
            height: 4.5cm;
            border-top: 2px solid #000;
            padding-top: 6px;
        }

        .legal-text {
            text-align: justify;
            font-style: italic;
            font-size: 9px;
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .estado-hint {
            font-size: 8px;
            margin-bottom: 8px;
        }

        .fecha-centro {
            text-align: center;
            font-size: 10px;
            margin-top: 6px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-box {
            text-align: center;
            width: 50%;
            padding-top: 4px;
        }

        .sig-line {
            border-top: 1px solid #000;
            width: 70%;
            margin: 0 auto 4px;
        }

        /* =========================================================
           TITULO
        ========================================================== */

        .title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 6px 0 10px 0;
            text-decoration: underline;
        }

        /* =========================================================
           INFORMACION GENERAL
        ========================================================== */

        .info-table-clean {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table-clean td {
            border: none;
            padding: 2px 4px;
            vertical-align: top;
            line-height: 1.2;
        }

        .label-cell {
            font-weight: bold;
            width: 16%;
            vertical-align: top;
        }

        .data-cell {
            width: 34%;
            vertical-align: top;
        }

        .double-line {
            border-top: 3px double #000;
            margin: 10px 0;
        }

        /* =========================================================
           TABLA DE BIENES
        ========================================================== */

        .bienes-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .bienes-table th,
        .bienes-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 8px;
            word-wrap: break-word;
        }

        .bienes-table th {
            background-color: #d9e1f2;
            font-weight: bold;
        }

        .text-left {
            text-align: left !important;
            white-space: normal;
        }

    </style>

</head>

<body>

    @include('pdf.partials.header')

    @include('pdf.partials.footer')

    {{-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== --}}

    <div class="title">
        ACTA DE TRANSFERENCIA DE BIENES
    </div>

    {{-- =========================================================
         INFORMACION GENERAL
    ========================================================== --}}

    <table class="info-table-clean">

        <tr>
            <td class="label-cell">QUIEN RECIBE:</td>
            <td class="data-cell">
                {{ strtoupper($movimiento->receptor->name) }}
            </td>

            <td class="label-cell">QUIEN ENTREGA:</td>
            <td class="data-cell">
                {{ strtoupper($movimiento->usuario->name) }}
            </td>
        </tr>

        <tr>
            <td class="label-cell">DNI:</td>
            <td class="data-cell">
                {{ $movimiento->receptor->dni }}
            </td>

            <td class="label-cell">DNI:</td>
            <td class="data-cell">
                {{ $movimiento->usuario->dni }}
            </td>
        </tr>

        <tr>
            <td class="label-cell">CORREO:</td>
            <td class="data-cell">
                {{ $movimiento->receptor->email ?? '---' }}
            </td>

            <td class="label-cell">CORREO:</td>
            <td class="data-cell">
                {{ $movimiento->usuario->email ?? '---' }}
            </td>
        </tr>

        <tr>
            <td class="label-cell">CELULAR:</td>
            <td class="data-cell">
                {{ $movimiento->receptor->telefono ?? '---' }}
            </td>

            <td class="label-cell">CELULAR:</td>
            <td class="data-cell">
                {{ $movimiento->usuario->telefono ?? '---' }}
            </td>
        </tr>

        <tr>
            <td class="label-cell">CENTRO DE COSTO:</td>

            <td colspan="3" class="data-cell">
                {{ strtoupper($movimiento->ubicacionDestino->denominacion ?? 'N/A') }}
            </td>
        </tr>

        <tr>
            <td class="label-cell">OBSERVACIÓN:</td>

            <td colspan="3" class="data-cell">
                {{ strtoupper($movimiento->observaciones_entrega ?? 'TRANSFERENCIA DE BIENES.') }}
            </td>
        </tr>

        <tr>
            <td class="label-cell">REFERENCIA:</td>

            <td colspan="3" class="data-cell">
                {{ $movimiento->codigo_referencia ?? 'OFICIO N° 0xxx-20xx-ADM-CE-CxxH-FCxxxCBB-UNA-PUNO' }}
            </td>
        </tr>

    </table>

    <div class="double-line"></div>

    {{-- =========================================================
         TABLA DE BIENES
    ========================================================== --}}

    <table class="bienes-table">

        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:14%">CODIGO</th>
                <th style="width:32%">DESCRIPCION</th>
                <th style="width:12%">MODELO</th>
                <th style="width:12%">MARCA</th>
                <th style="width:14%">SERIE</th>
                <th style="width:12%">ESTADO</th>
            </tr>
        </thead>

        <tbody>

            @foreach($movimiento->movimientosActivos as $index => $item)

            <tr>

                <td>
                    {{ $index + 1 }}
                </td>

                <td>
                    {{ $item->activo->codigo }}
                </td>

                <td class="text-left">
                    {{ $item->activo->denominacion }}
                </td>

                <td>
                    {{ $item->activo->modelo ?? '-' }}
                </td>

                <td>
                    {{ $item->activo->marca ?? '-' }}
                </td>

                <td>
                    {{ $item->activo->numero_serie ?? '-' }}
                </td>

                <td>
                    {{ strtoupper($item->activo->estado_conservacion ?? 'REGULAR') }}
                </td>

            </tr>

            @endforeach

        </tbody>

    </table>

</body>
</html>