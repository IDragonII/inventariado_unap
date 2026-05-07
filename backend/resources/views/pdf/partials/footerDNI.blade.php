<div id="page-footer">
    <div class="legal-text">
        Se procedio con la descripción a detalle de los bienes a transferir, y al firmar el presente
        documento, quien recibe asume la responsabilidad del bien(es) que esta asumiendo esto sobre
        la custodia, existencia, permanencia, conservacion y funcionamiento de los bienes, por otra
        parte quien entrega los bienes tiene la reponsabilidad de entregar una copia de la presente
        acta a la sub unidad de patrimonio para que se actualice la informacion del control
        patrimonial. En señal de conformidad ambos suscriben la presente acta.
    </div>

    <div class="fecha-centro">
        {{ \Carbon\Carbon::parse($movimiento->fecha_movimiento)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
    </div>
</div>