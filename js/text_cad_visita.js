function toggleTextareaRows(id) {
    var field = document.getElementById(id);
    if (!field) return;
    var expanded = field.dataset.expanded === '1';
    field.dataset.expanded = expanded ? '0' : '1';
    field.rows = expanded ? 2 : 30;
    field.style.setProperty('height', expanded ? '30px' : '420px', 'important');
    field.style.setProperty('min-height', expanded ? '30px' : '420px', 'important');
    field.style.setProperty('overflow-y', expanded ? 'hidden' : 'auto', 'important');
}

function aumentarTextAudit() {
    toggleTextareaRows('rel_visita_vis');
}

function aumentarTextAcoes() {
    toggleTextareaRows('acoes_int_vis');
}

function aumentarTextExamesEnf() {
    toggleTextareaRows('exames_enf');
}

function aumentarTextProgEnf() {
    toggleTextareaRows('programacao_enf');
}

function aumentarTextProgVis() {
    toggleTextareaRows('programacao_enf');
}

function aumentarTextOportEnf() {
    toggleTextareaRows('oportunidades_enf');
}
 $(document).ready(function() {
     // Adicione um ouvinte de mudança ao checkbox button
     $('#exibirVisita').change(function() {
         // Verifique se o checkbox button está marcado
         if ($(this).is(':checked')) {
             // Se estiver marcado, mostre a div
             $('#div-visitas').show();
             $('#textVisita').text('Ocultar visitas');

         } else {
             // Se não estiver marcado, oculte a div
             $('#div-visitas').hide();
             $('#textVisita').text('Exibir visitas anteriores');

         }
     });
 });


 // aparecer campos relatorio detalhado
 (function() {
     function toggleDetalhesVanilla() {
         var select = document.getElementById('relatorio-detalhado');
         var wrapper = document.getElementById('detalhes-card-wrapper');
         var detalhes = document.getElementById('div-detalhado');
         if (!select || !wrapper || !detalhes) return;
         var show = select.value === 's';
         wrapper.style.display = show ? 'block' : 'none';
         detalhes.style.display = show ? 'block' : 'none';
     }

     if (window.jQuery) {
         $(document).ready(function() {
             function toggleDetalhes() {
                 if ($('#relatorio-detalhado').val() === 's') {
                     $('#detalhes-card-wrapper').show();
                     $('#div-detalhado').css('display', 'block');
                 } else {
                     $('#div-detalhado').hide();
                     $('#detalhes-card-wrapper').hide();
                 }
             }

             toggleDetalhes();
             $('#relatorio-detalhado').change(toggleDetalhes);
         });
     }

     document.addEventListener('DOMContentLoaded', function() {
         toggleDetalhesVanilla();
         var select = document.getElementById('relatorio-detalhado');
         if (select) {
             select.addEventListener('change', toggleDetalhesVanilla);
         }
     });
 })();

 // aparecer campo atb em uso
 $(document).ready(function() {
     $('#atb').hide(); // Oculta o campo de texto quando a página carrega

     $('#atb_enf').change(function() {
         if ($(this).val() === 's') {
             $('#atb').show();
         } else {
             $('#atb').hide();
         }
     });
 });
 // aparecer campo litros de O2
 $(document).ready(function() {
     $('#div-oxig').hide(); // Oculta o campo de texto quando a página carrega

     $('#oxig_enf').change(function() {
         if ($(this).val() === 'Cateter' || $(this).val() == 'Mascara') {
             $('#div-oxig').show();
         } else {
             $('#div-oxig').hide();
         }
     });
 });
