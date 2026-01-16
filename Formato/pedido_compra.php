<?
function genera_pdf_doc_comp($pedi, $id, $idempresa, $idsucursal)
{

    session_start();
    global $DSN_Ifx, $DSN;

    $oIfxA = new Dbo();
    $oIfxA->DSN = $DSN_Ifx;
    $oIfxA->Conectar();

    $oIfxB = new Dbo();
    $oIfxB->DSN = $DSN_Ifx;
    $oIfxB->Conectar();

    $oIfx = new Dbo;
    $oIfx->DSN = $DSN_Ifx;
    $oIfx->Conectar();

    $oCon = new Dbo;
    $oCon->DSN = $DSN;
    $oCon->Conectar();


    unset($_SESSION['pdf']);
    $oReturn = new xajaxResponse();

    $usuario_web = $_SESSION['U_ID'];

    try{


    $sqlest = "select pedi_est_pedi from saepedi 
    where pedi_cod_empr=$idempresa and pedi_cod_sucu=$idsucursal
    and pedi_cod_pedi='$pedi'";

    $est = consulta_string_func($sqlest, 'pedi_est_pedi', $oIfxA, '');

//LOGO DEL REPORTE
    $sql = "select empr_web_color, empr_path_logo, empr_nom_empr from saeempr where empr_cod_empr =  $idempresa ";

    if ($oIfx->Query($sql)) {
        if ($oIfx->NumFilas() > 0) {
            $empr_path_logo = $oIfx->f('empr_path_logo');
            $empr_color = $oIfx->f('empr_web_color');
            $razonSocial = $oIfx->f('empr_nom_empr');
        }
    }
    $oIfx->Free();
    $path_img = explode("/", $empr_path_logo);
    $count = count($path_img) - 1;
    $arc_img = DIR_FACTELEC . 'Include/Clases/Formulario/Plugins/reloj/' . $path_img[$count];

    if (file_exists($arc_img)) {
        $imagen = $arc_img;
    } else {
        $imagen = '';
    }
    $logo = '';
 
    if ($imagen != '') {

        $empr_logo = '<img src="' . $imagen . '" width="80px" />';
        
    }
    else{
       $empr_logo = '<div style="color:red">LOGO CARGADO</div>'; 
    }
//SOLICITANTE
    $sqlsol = "select pedi_cod_empl, pedi_res_pedi from saepedi where pedi_cod_pedi='$pedi' and pedi_cod_empr=$idempresa and pedi_cod_sucu=$idsucursal";
    $ced = consulta_string_func($sqlsol, 'pedi_cod_empl', $oIfxA, '');
    $responsable = consulta_string_func($sqlsol, 'pedi_res_pedi', $oIfxA, '');
    
//FECHA DE DEL PEDIDO
    $sfecha = "SELECT pedi_fec_pedi, pedi_det_pedi, pedi_uso_pedi, pedi_lug_entr, pedi_cod_sucu, pedi_tip_sol 
    from saepedi where pedi_cod_pedi='$pedi' and pedi_cod_empr=$idempresa and pedi_cod_sucu=$idsucursal";

    $tipo_pedido = consulta_string_func($sfecha, 'pedi_tip_sol', $oIfx, '');
    $fecha_pedido = consulta_string_func($sfecha, 'pedi_fec_pedi', $oIfx, '');
    $smotivo = strtoupper(nl2br(consulta_string_func($sfecha, 'pedi_det_pedi', $oIfx, '')));
    $suso = strtoupper(consulta_string_func($sfecha, 'pedi_uso_pedi', $oIfx, ''));
    $slugar = strtoupper(consulta_string_func($sfecha, 'pedi_lug_entr', $oIfx, ''));
    $fecha_pedido = date("d/m/Y", strtotime($fecha_pedido));
    $sucursal = consulta_string_func($sfecha, 'pedi_cod_sucu', $oIfx, 0);
    
    
    $spedi = "select sucu_nom_sucu from saesucu where sucu_cod_sucu=$sucursal";
    $nombre_sucursal = consulta_string_func($spedi, 'sucu_nom_sucu', $oIfx, '');

///ARE SOLICITANTE
    $sqla = "select pedi_des_cons, pedi_are_soli from saepedi where pedi_cod_pedi='$pedi' and pedi_cod_empr=$idempresa and pedi_cod_sucu=$idsucursal";
    $sarea = strtoupper(consulta_string_func($sqla, 'pedi_are_soli', $oIfx, 0));
    $obs_pedido= strtoupper(nl2br(consulta_string_func($sqla, 'pedi_des_cons', $oIfx, '')));


    //NOMBRE DE LA AREA SOLICITANTE
    $sqlarea = "select area_des_area from saearea where area_cod_area='$sarea'";
    $nombre_area = consulta_string_func($sqlarea, 'area_des_area', $oIfx, '');


    //DETALLE DEL PEDIDO
    $sql = "SELECT  dped_cod_dped,    dped_cod_pedi,  dped_cod_prod,
                    dped_cod_bode,    dped_cod_sucu,  dped_cod_empr,
                    dped_num_prdo,    dped_cod_ejer,  dped_cod_unid,
                    dped_can_ped,     dped_can_ent,   dped_can_ped,
                    dped_prc_dped,    dped_ban_dped,  dped_costo_dped,
                    dped_tot_dped,    dped_prod_nom,  dped_cod_ccos,
                    dped_det_dped,    dped_pre_dped, dped_cod_auxiliar,
                    dped_desc_auxiliar
                    from saedped
                    where dped_cod_pedi='$pedi' and dped_cod_empr= $idempresa and dped_cod_sucu = $idsucursal
                    and dped_cod_dped not in(select dped_cod_dped from saedped where dped_est_dped ='1')";

    $des = '';
    $i = 1;
    $total_pre=0;
    if ($oIfx->Query($sql)) {
        if ($oIfx->NumFilas() > 0) {
            do {
                $cantidad = $oIfx->f('dped_can_ped');
                $cantidad = round($cantidad, 2);
                $unidad = $oIfx->f('dped_cod_unid');
                $detaprod = mb_strtoupper($oIfx->f('dped_det_dped'),'utf-8');
                $cos = $oIfx->f('dped_cod_ccos');
                $presupuesto = $oIfx->f('dped_pre_dped');

                $cod_aux = $oIfx->f('dped_cod_auxiliar');
                $desc_aux = mb_strtoupper($oIfx->f('dped_desc_auxiliar'),'utf-8');

                $citem=$oIfx->f('dped_cod_prod');

                $sqltip="select prod_cod_tpro from saeprod where prod_cod_prod='$citem' and prod_cod_empr=$idempresa";
                $tip=consulta_string($sqltip, 'prod_cod_tpro', $oIfxA, 0);

                /*if(empty($detalle)&&$tip==2){
                    $detalle=$oIfx->f('dped_prod_nom');
                }*/
               
                $nom_prod = mb_strtoupper($oIfx->f('dped_prod_nom'),'utf-8');
                //$detalle=$nom_prod.' '.$detaprod;
                $total_pre+=$presupuesto;
                $presupuesto = number_format($presupuesto, 2);
                $tec = $oIfx->f('dped_aut_tecn');

                if (empty($tec)) {
                    $tec = '';
                }
                if (empty($presupuesto)) {
                    $presupuesto = '0';
                }
                if (empty($unidad)) {
                    $unidad = 0;
                }

                $sqlu = "select unid_sigl_unid from saeunid where unid_cod_unid=$unidad";
                if ($oIfxA->Query($sqlu)) {
                    if ($oIfxA->NumFilas() > 0) {
                        $sigla = $oIfxA->f('unid_sigl_unid');
                    }
                }
                $oIfxA->Free();

                if(!empty($cod_aux)){
                        $nom_prod = $desc_aux;
                        $citem = '<b>'.$cod_aux.'</b>';
                }

                //SERVICIOS
                if($tipo_pedido==1){
                    $des .= ' <tr >
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="4%"  >' . $i . '</td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="22%">' . $citem . '<br></td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="left" width="60%">' . $nom_prod . '<br>'.$detaprod.'</td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="7%"> ' . $sigla . '<br></td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="7%"> ' . $cantidad . '<br></td>';
                    $des .= '</tr>';
                }
                else{
                    
                    $des .= ' <tr >
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="4%"  >' . $i . '</td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="20%">' . $citem . '<br></td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="left" width="40%">' . $nom_prod . '<br></td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="7%"> ' . $sigla . '<br></td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="center" width="7%"> ' . $cantidad . '<br></td>
                            <td style="border-bottom:1px solid black; font-size:90%;"align="left" width="22%"> ' . $detaprod . '<br></td>';
                    $des .= '</tr>';

                }

                $i++;
            } while ($oIfx->SiguienteRegistro());
        }
    }
    $oIfx->Free();

    


///DISEÑO DEL REPORTE

$fecha_impresion = date('d/m/Y H:i:s');

//CABECERA DEATLLE PEDIDO

//SERVICIOS
if($tipo_pedido==1){

    $cab_detalle='<table  border="0"  cellpadding="0">
    <tr>
       
        <td style = "font-size:90%;" align="center" width="4%;" border="1px;"> <strong>N°</strong></td>
        <td style = "font-size:90%;" align="center" width="22%;" border="1px;"> <strong>CODIGO</strong></td>
        <td style = "font-size:90%;" align="center" width="60%;" border="1px;"> <strong>DETALLE DEL SERVICIO</strong></td>
        <td style = "font-size:90%;" align="center" width="7%;" border="1px;"> <strong>UNID.</strong></td>
        <td style = "font-size:90%;" align="center" width="7%;" border="1px;"> <strong>CANT.</strong></td>
    </tr>';

}//PRODUCTOS
else{
    $cab_detalle='<table  border="0"  cellpadding="0">
    <tr>
       
        <td style = "font-size:90%;" align="center" width="4%;" border="1px;"> <strong>N°</strong></td>
        <td style = "font-size:90%;" align="center" width="20%;" border="1px;"> <strong>CODIGO</strong></td>
        <td style = "font-size:90%;" align="center" width="40%;" border="1px;"> <strong>PRODUCTO</strong></td>
        <td style = "font-size:90%;" align="center" width="7%;" border="1px;"> <strong>UNID.</strong></td>
        <td style = "font-size:90%;" align="center" width="7%;" border="1px;"> <strong>CANT.</strong></td>
        <td style = "font-size:90%;" align="center" width="22%;" border="1px;"> <strong>USO/DETALLE</strong></td> 
    </tr>';
}


//INFORRMACION DE LOS APROBADORES

    $firmas_html = ''; // Aquí se almacenará el contenido de la tabla
    $contador = 0;
    $fila_cargos = '';
    $fila_nombres = '';

    $solicitado_por = '';

    $sql="SELECT
        ap.id,
        ap.empresa,
        ap.sucursal,
        ap.pedido,
        ap.aprobador_id,
        ap.aprobador_nombre,
        ap.cargo_id,
        ac.nombre     AS cargo_nombre,
        ap.enviar,
        ap.creado_en
    FROM comercial.aprobador_pedido ap
    LEFT JOIN comercial.aprobador_cargo ac
        ON ac.id = ap.cargo_id
    WHERE ap.pedido::integer = $pedi and ap.empresa = $idempresa and ap.sucursal = $idsucursal
    ORDER BY ap.orden asc;";

    if ($oIfx->Query($sql)) {
        if ($oIfx->NumFilas() > 0) {
            do {

                $cargo = $oIfx->f('cargo_nombre');
                $nombre = $oIfx->f('aprobador_nombre');

                if(trim(strtoupper($cargo))=='SOLICITADO POR' || trim(strtoupper($cargo))=='SOLICITADO POR:'){
                    $solicitado_por = $nombre;
                }
                elseif(trim(strtoupper($cargo))=='ELABORADO POR' || trim(strtoupper($cargo))=='ELABORADO POR:'){
                    $nombre = $responsable;
                }


                // Celda para cargos
                $fila_cargos .= '<td align="center" style="width:25%;">_____________________</td>';

                // Celda para nombres de los aprobadores
                $fila_nombres .= '<td align="center" style="width:25%;"><b>'.$cargo.'</b><br>'.$nombre.'<br><br></td>';
                
                $contador++;

                // Cada 2 elementos, cerrar fila y reiniciar variables
                if ($contador % 4 === 0) {
                    $firmas_html .= '<tr >' . $fila_cargos . '</tr>';
                    $firmas_html .= '<tr>' . $fila_nombres . '</tr>';
                    $fila_cargos = '';
                    $fila_nombres = '';
                }

            
            } while ($oIfx->SiguienteRegistro());

            // Si quedó uno solo (impar), cerramos filas con una celda vacía
            if ($contador % 2 !== 0) {
                $fila_cargos .= '<td></td>';
                $fila_nombres .= '<td><br><br></td>';
                $firmas_html .= '<tr>' . $fila_cargos . '</tr>';
                $firmas_html .= '<tr>' . $fila_nombres . '</tr>';
            }
        }
    }
    $oIfx->Free();

    //SECUENCIAL

    $secuencial_pedido= cero_mas_func('0', 9 - strlen($pedi)).$pedi;


$html = <<<EOD
<table  border="0"  cellpadding="1" >
    <tr>
        <td colspan="3" style="font-size:8px;" with="100%" align="rigth"  >Impreso el $fecha_impresion</td>
    </tr>
    <tr>
        <td colspan="3" width="100%" style="font-size:10px;"align="center"><b>$razonSocial </b></td>
    </tr>
    
    <tr>
        <td valign="top" width="20%" rowspan="8">$empr_logo</td>
        <td colspan="2" style="font-size:8px;"align="right" width="80%"><b>FECHA PEDIDO: </b>$fecha_pedido </td>
    </tr>

    <tr>
        <td colspan="2" width="80%" style="font-size:12px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>NOTA DE PEDIDO # : $secuencial_pedido</b></td>
    </tr>
    <tr>
        <td style="width: 20%; font-size:9px;">&nbsp;</td>
        <td style="width: 60%; font-size:9px;">&nbsp;</td>
    </tr>
    <tr>
        <td style="width: 20%; font-size:9px;"><b>ELABORADO POR: </b></td>
        <td style="width: 60%; font-size:9px;">$responsable</td>
    </tr>

    <tr>
        <td style="width: 20%; font-size:9px;"><b>SOLICITADO POR: </b></td>
        <td style="width: 60%; font-size:9px;">$slugar</td>
    </tr>

    <tr>
        <td style="width: 20%; font-size:9px;"><b>AREA SOLICITA: </b></td>
        <td style="width: 60%; font-size:9px;">$nombre_area</td>
    </tr>

    <tr>
        <td style="width: 20%; font-size:9px;"><b>MOTIVO PEDIDO: </b></td>
        <td style="width: 60%; font-size:9px;">$smotivo</td>
    </tr>

    <tr>
        <td style="width: 20%; font-size:9px;"><b>DESCRIPCIÓN: </b></td>
        <td style="width: 60%; font-size:9px;">$obs_pedido</td>
    </tr>
    
    </table><br><br>$cab_detalle $des
    </table><div class="tabla-container"><br><br><br><br><table  border="0"  cellpadding="2" >
    $firmas_html
</table></div>
EOD;

} catch (Exception $e) {
  
    echo $e->getMessage();
  
}
    if ($id == 1) {

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        //$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetMargins(10,10, 10, true); 
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        //$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // set font
        $pdf->SetFont('helvetica', 'N', 10);
        // add a page
        $pdf->AddPage();
  
        $pdf->writeHTMLCell(0, 0, '', '',$html, 0, 1, 0, true, '', true); 

        $fecha = date('d-m-Y H:i:s');
        $docu = 'solicitud_compra' . $pedi . '.pdf';

        $ruta = DIR_FACTELEC . 'Include/archivos';
        if (!file_exists($ruta)){
            mkdir($ruta);
        }

        $ruta = DIR_FACTELEC . 'Include/archivos/solicitudes_compras';
        if (!file_exists($ruta)){
            mkdir($ruta);
        }
        $ruta = DIR_FACTELEC . 'Include/archivos/solicitudes_compras/' . $docu;

        $pdf->Output($ruta, 'F');
        return $ruta;
    } else {
        return $html;
    }
}

?>