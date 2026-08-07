<!-- Modal Ventas del día -->
<div class="modal fade" id="ventasModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Ventas del día</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="ventasHoyContenido">
                    <div class="text-center py-4">
                        <div class="spinner-border text-info" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Cargando...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('ventasModal').addEventListener('show.bs.modal', function () {
    fetch('{{ route("punto_venta.ventas_hoy") }}', {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('ventasHoyContenido').innerHTML = html;
    })
    .catch(() => {
        document.getElementById('ventasHoyContenido').innerHTML = '<p class="text-danger text-center py-4">Error al cargar las ventas.</p>';
    });
});
</script>