<?php
$geMenus=[
 'Acompanhamento'=>['bi-clipboard2-pulse',['Painel de casos'=>'gestor_estipulante.php','Pendências'=>'gestor_estipulante.php?filtro=atrasadas','Casos encerrados'=>'gestor_estipulante.php?filtro=encerrados']],
 'Cadastros'=>['bi-folder2-open',['Pacientes'=>'list_paciente.php','Hospitais'=>'list_hospital.php']],
 'Internações'=>['bi-hospital',['Lista de internações'=>'list_internacao.php','Censo hospitalar'=>'list_censo.php']],
 'BI'=>['bi-bar-chart',['Indicadores'=>'bi_navegacao.php','Tipos de internação'=>'TipoInternacaoBI.php','Longa permanência'=>'LongaPermanenciaBI.php']],
];
if(ge_writer()) {
 $geMenus['Cadastros'][1]+=['Cadastrar paciente'=>'cad_paciente.php','Cadastrar hospital'=>'cad_hospital.php'];
 $geMenus['Internações'][1]+=['Registrar internação'=>'cad_internacao.php','Registrar censo'=>'cad_censo.php','Registrar altas'=>'list_internacao_gerar_alta.php'];
}
foreach($geMenus as $label=>[$icon,$items]): ?>
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi <?=htmlspecialchars($icon)?>"></i> <?=htmlspecialchars($label)?></a><ul class="dropdown-menu">
<?php foreach($items as $name=>$path):?><li><a class="dropdown-item" href="<?=htmlspecialchars($BASE_URL.$path,ENT_QUOTES,'UTF-8')?>"><?=htmlspecialchars($name)?></a></li><?php endforeach;?></ul></li>
<?php endforeach;?>
