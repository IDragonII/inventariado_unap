<div style="padding: 5px;">
    <div style="text-align: center;">
        <p style="margin: 5px 0; font-size: 14px; font-weight: bold;">
            LEVANTAMIENTO DE INFORMACIÓN - OTI 2025
        </p>
        <p style="margin: 5px 0; font-size: 12px; font-weight: bold; color: #333;">
            FORMATO DE TOMA DE INVENTARIO DE SOFTWARE
        </p>
    </div>

    @if($area || $responsableSoftware)
    <table width="100%" style="font-size: 10px; margin-top: 10px;">
        <tr>
            <td style="width: 50%;">
                @if($area)
                    <strong>UBICACIÓN:</strong>
                    {{ $area->oficina->denominacion ?? '' }}<br>
                    @if($area->aula)
                        - {{ $area->aula }}
                    @endif
                @endif
                @if($responsableSoftware)
                    <br><strong>Encargado de oficina:</strong>
                    {{ $responsable->dni ?? '' }} - {{ $responsable->name ?? '' }}
                @endif
            </td>
            <td style="width: 50%; text-align: right;">
                Fecha: {{ date('d/m/Y') }}
            </td>
        </tr>
    </table>
    @endif
</div>
