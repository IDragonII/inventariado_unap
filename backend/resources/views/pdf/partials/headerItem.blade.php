<div id="page-header">
    <div class="header-inner" style="display: table; width: 100%;">
        {{-- Lado Izquierdo: Logo --}}
        <div class="logo-box" style="display: table-cell; width: 65px; vertical-align: middle;">
            @if($logo)
                <img src="{{ $logo }}" style="width:55px; display:block;">
            @else
                <div class="logo-placeholder">LOGO<br>UNA</div>
            @endif
        </div>

        {{-- Centro: Texto de la Universidad --}}
        <div class="header-text" style="display: table-cell; vertical-align: middle; text-align: left;">
            Universidad Nacional del Altiplano<br>
            Unidad de abastecimiento<br>
            Sub Unidad de Patrimonio
        </div>

        {{-- Lado Derecho: Año --}}
        <div style="display: table-cell; vertical-align: middle; text-align: right; font-weight: bold; font-size: 12px;">
            AÑO: {{ date('Y') }}
        </div>
    </div>
</div>