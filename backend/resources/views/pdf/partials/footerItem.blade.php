<style>
    /* El estilo debe estar aquí para que el partial lo inyecte, 
       pero sin etiquetas de estructura de página */
    #page-footer {
        position: fixed;
        bottom: -5.5cm; 
        left: 0;
        right: 0;
        height: 5.5cm;
        width: 100%;
    }

    .footer-content {
        width: 100%;
        border-top: 1.5px solid #000; 
        padding-top: 8px;
    }

    .legal-text {
        text-align: justify;
        font-size: 10.5px;
        line-height: 1.4;
        margin-bottom: 12px;
        color: #000;
    }

    .fecha-puno {
        font-size: 11px;
        margin-bottom: 15px;
        text-align: left;
    }

    .cc-archivo-box {
        margin-top: 50px; /* Hueco para el sello */
        font-size: 11px;
        line-height: 1.3;
        text-align: left;
        padding-left: 0;
    }
</style>

<div id="page-footer">
    <div class="footer-content">
        <div class="legal-text">
            Los bienes descritos en el presente documento están considerados bajo su responsabilidad, según consta la documentación de control patrimonial de la Sub Unidad de Patrimonio. Se le recomienda la custodia, existencia, permanencia, conservación y buen uso de los bienes.
        </div>
        
        <div class="fecha-puno">
            Puno (C.U.), &nbsp;&nbsp; {{ date('d') }} &nbsp;&nbsp; de {{ strtoupper(\Carbon\Carbon::now()->locale('es')->getTranslatedMonthName()) }} del {{ date('Y') }}
        </div>

        <div class="cc-archivo-box">
            C.C.<br>
            Archivo
        </div>
    </div>
</div>