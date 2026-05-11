<div id="page-header">
    <div class="header-inner" style="display: table; width: 100%;">
        <div class="logo-box" style="display: table-cell; width: 65px; vertical-align: middle;">
            @if($logo)
                @if(str_starts_with($logo, 'data:'))
                    <img src="{{ $logo }}" style="width:55px; display:block;">
                @elseif(file_exists($logo))
                    <img src="{{ $logo }}" style="width:55px; display:block;">
                @else
                    <div class="logo-placeholder">LOGO<br>UNA</div>
                @endif
            @else
                <div class="logo-placeholder">LOGO<br>UNA</div>
            @endif
        </div>

        <div class="header-text" style="display: table-cell; vertical-align: middle; text-align: left;">
            Universidad Nacional del Altiplano<br>
            Unidad de abastecimiento<br>
            Sub Unidad de Patrimonio
        </div>

        <div style="display: table-cell; vertical-align: middle; text-align: right; font-weight: bold; font-size: 12px;">
            AÑO: {{ date('Y') }}
        </div>
    </div>
</div>