(function ($) {
  'use strict';

  $(document).ready(function () {
    const $form = $('#gr-upload-form');
    const $progress = $('#gr-progress');
    const $percent = $('#gr-progress-percent');
    const $bar = $('#gr-progress-bar');
    const $stats = $('#gr-stats-line');
    const $rows = $('#gr-product-rows');

    let total = 0;

    $form.on('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);

      $progress.removeClass('hidden');
      $bar.css('width', '0%');
      $percent.text('0');
      $stats.text('Préparation…');

      $.ajax({
        url: GR_ADMIN.ajax,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: (res) => {
          if (res.success) {
            total = res.data.total - 1;
            $stats.text(`Fichier chargé (${total} lignes).`);
            processChunk(0); // on démarre à 0
          } else {
            alert('Erreur: ' + JSON.stringify(res.data));
          }
        },
        error: (xhr) => {
          alert('Erreur upload: ' + xhr.responseText);
        }
      });
    });

    function processChunk() {
      $.ajax({
        url: GR_ADMIN.ajax,
        type: 'POST',
        data: {
          action: 'gr_process_chunk',
          nonce: GR_ADMIN.nonce,
          chunk: 150
        },
        success: (res) => {
          if (!res.success) {
            $stats.text('Erreur: ' + JSON.stringify(res.data));
            return;
          }

          const d = res.data;

          const percent = Math.min(100, Math.round(d.percent));
          $percent.text(percent);
          $bar.css('width', percent + '%');

          $stats.text(
            `Créés: ${d.created}, MAJ: ${d.updated}, Ignorés: ${d.skipped}`
          );

          $rows.empty();
          Object.entries(d.perProduct).forEach(([pid, info]) => {
            $rows.append(`
          <tr>
            <td class="py-1">${info.name}</td>
            <td class="py-1">${pid}</td>
            <td class="py-1">${info.added}</td>
            <td class="py-1">${info.updated}</td>
            <td class="py-1">${info.skipped}</td>
            <td class="py-1">${info.avg || '-'}</td>
            <td class="py-1">${info.count || '-'}</td>
          </tr>
        `);
          });

          // Si pas terminé on relance après un petit délai
          if (!d.complete) {
            setTimeout(processChunk, 300);
          } else {
            $percent.text('100');
            $bar.css('width', '100%');
            $stats.html(`
          <span class="flex items-center gap-2 text-emerald-600 font-medium">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="none">
                  <circle cx="16" cy="16" r="16" fill="#19d873"></circle>
                  <path d="M14 17.9L10.1 14l-2.1 2.1 6 6 10-10-2.1-2.1z" fill="#e6e6e6"/>
              </svg>
              Import terminé.
          </span>
        `);
          }

        },
        error: (xhr) => {
          $stats.text('Erreur AJAX: ' + xhr.responseText);
        }
      });
    }

  });

})(jQuery);
