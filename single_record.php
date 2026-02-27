<?php
include 'includes/db.php';
include 'includes/header.php';

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$exportType = $_GET['export'] ?? null;

if(!isset($_GET['id'])){
    die("Invalid Record ID");
}

$record_id = intval($_GET['id']);
$mode = (isset($_GET['mode']) && $_GET['mode'] == 'edit') ? 'edit' : 'view';

/* FETCH RECORD */
$record_query = mysqli_query($conn, "SELECT * FROM records WHERE id = $record_id");
if(mysqli_num_rows($record_query) == 0){
    die("Record not found");
}
$record = mysqli_fetch_assoc($record_query);

/* FETCH VALUES */
$values = [];
$res = mysqli_query($conn,"SELECT field_id,value FROM record_values WHERE record_id=$record_id");
while($r=mysqli_fetch_assoc($res)){
    $values[$r['field_id']]=$r['value'];
}

/* EXPORT */
if(isset($_GET['export']) && $mode=='view'){

    $fields_result = mysqli_query($conn,"SELECT * FROM fields WHERE status=1 ORDER BY id ASC");
    $fields = [];
    while($f = mysqli_fetch_assoc($fields_result)){
        $fields[] = $f;
    }

    /* ================= CSV ================= */
    if($exportType === 'csv'){

        if (ob_get_length()) ob_end_clean();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=record_'.$record_id.'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output','w');

        $header = ['Field Name','Value'];
        fputcsv($output,$header);

        foreach($fields as $field){
            $value = $values[$field['id']] ?? '';
            fputcsv($output, [$field['field_name'],$value]);
        }

        fclose($output);
        exit;
    }

    /* ================= EXCEL ================= */
    if($exportType === 'excel'){

        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=record_'.$record_id.'.xls');

        echo "Field Name\tValue\n";

        foreach($fields as $field){
            $value = $values[$field['id']] ?? '';
            echo $field['field_name']."\t".$value."\n";
        }

        exit;
    }

    /* ================= PDF ================= */
    if ($exportType === 'pdf') {

    if (ob_get_length()) ob_end_clean();

    require(__DIR__ . '/includes/FPDF/fpdf.php');

    class PDF extends FPDF {

        function Header(){
            $this->SetFont('Arial','B',18);
            $this->Cell(0,12,'Records Report',0,1,'C');
            $this->Ln(5);
        }

        function Footer(){
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        }

        function Row($data, $widths){

            $nb = 0;
            for($i=0;$i<count($data);$i++){
                $nb = max($nb, $this->NbLines($widths[$i], $data[$i]));
            }

            $h = 7 * $nb;

            if($this->GetY()+$h > $this->PageBreakTrigger)
                $this->AddPage();

            for($i=0;$i<count($data);$i++){

                $w = $widths[$i];
                $x = $this->GetX();
                $y = $this->GetY();

                $this->Rect($x,$y,$w,$h);
                $this->MultiCell($w,7,$data[$i],0,'L');
                $this->SetXY($x+$w,$y);
            }

            $this->Ln($h);
        }

        function NbLines($w,$txt){
            $cw=&$this->CurrentFont['cw'];
            if($w==0)
                $w=$this->w-$this->rMargin-$this->x;
            $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
            $s=str_replace("\r",'',$txt);
            $nb=strlen($s);
            if($nb>0 && $s[$nb-1]=="\n")
                $nb--;
            $sep=-1;
            $i=0;
            $j=0;
            $l=0;
            $nl=1;
            while($i<$nb){
                $c=$s[$i];
                if($c=="\n"){
                    $i++;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                    continue;
                }
                if($c==' ')
                    $sep=$i;
                $l+=$cw[$c];
                if($l>$wmax){
                    if($sep==-1){
                        if($i==$j)
                            $i++;
                    }
                    else
                        $i=$sep+1;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                }
                else
                    $i++;
            }
            return $nl;
        }
    }

    $pdf = new PDF('P','mm','A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',10);

    /* Fetch Fields */
    $fields_result = mysqli_query($conn,"SELECT * FROM fields WHERE status=1 ORDER BY id ASC");
    $fields = [];
    while($f=mysqli_fetch_assoc($fields_result)){
        $fields[]=$f;
    }

    /* Column Widths */
    $widths = [12,30]; // S.No, Created

    $dynamicWidth = floor((190 - 12 - 30 - 25) / count($fields));

    foreach($fields as $f){
        $widths[] = $dynamicWidth;
    }

    $widths[] = 25; // Status

    /* Header Row */
    $pdf->SetFillColor(220,220,220);

    $headers = ['S.No','Created'];
    foreach($fields as $f){
        $headers[] = $f['field_name'];
    }
    $headers[] = 'Status';

    for($i=0;$i<count($headers);$i++){
        $pdf->Cell($widths[$i],10,$headers[$i],1,0,'L',true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial','',9);

    /* Build Only ONE ROW */
    $row = [];
    $row[] = 1;
    $row[] = date('d M Y H:i',strtotime($record['created_at']));

    foreach($fields as $f){
        $row[] = $values[$f['id']] ?? '';
    }

    $row[] = $record['is_done'] ? 'Done' : 'Pending';

    $pdf->Row($row,$widths);

    $pdf->Output('D','record_'.$record_id.'.pdf');
    exit;
} 
}

/* TOGGLE DONE */
if(isset($_POST['toggle_done']) && $mode=='view'){
    $new=$record['is_done']?0:1;
    mysqli_query($conn,"UPDATE records SET is_done=$new,updated_at=NOW() WHERE id=$record_id");
    header("Location:single_record.php?id=$record_id");
    exit;
}

/* UPDATE */
if(isset($_POST['update_record']) && $mode=='edit'){
    foreach($_POST as $fid=>$val){
        if($fid=='update_record') continue;
        $fid=intval($fid);
        $val=mysqli_real_escape_string($conn,$val);

        $check=mysqli_query($conn,"SELECT id FROM record_values WHERE record_id=$record_id AND field_id=$fid");
        if(mysqli_num_rows($check)>0){
            mysqli_query($conn,"UPDATE record_values SET value='$val' WHERE record_id=$record_id AND field_id=$fid");
        }else{
            mysqli_query($conn,"INSERT INTO record_values(record_id,field_id,value) VALUES($record_id,$fid,'$val')");
        }
    }
    mysqli_query($conn,"UPDATE records SET updated_at=NOW() WHERE id=$record_id");
    header("Location:single_record.php?id=$record_id");
    exit;
}

$fields_query=mysqli_query($conn,"SELECT * FROM fields WHERE status=1 ORDER BY id ASC");
?>

<style>
body{
    background:#f4f6f9;
    margin:0;
    font-family:'Segoe UI',sans-serif;
}

/* FULL WIDTH IN EDIT MODE */
.container{
    margin:30px auto;
    padding:0 30px;
}

/* View mode centered */
.view-mode-wrapper{
    max-width:1100px;
    margin:auto;
}

/* Edit mode full width */
.edit-mode-wrapper{
    width:100%;
}

/* Card */
.card{
    background:#fff;
    padding:35px;
    border-radius:10px;
    box-shadow:0 6px 18px rgba(0,0,0,.05);
    border:1px solid #e6e9ef;
}

/* Grid */
.form-grid{
    display:grid;
    gap:25px;
}

.view-mode .form-grid{
    grid-template-columns:repeat(2,1fr);
}

/* EDIT = FULL WIDTH */
.edit-mode .form-grid{
    grid-template-columns:1fr;
}

/* Field */
.field-block label{
    font-weight:600;
    margin-bottom:8px;
    display:block;
}

/* View box */
.field-value{
    background:#f8f9fb;
    border:1px solid #dee2e6;
    border-radius:6px;
    padding:12px;
    white-space:pre-wrap;
    word-break:break-word;
}

/* Inputs */
input,textarea,select{
    width:100%;
    padding:14px;
    border-radius:6px;
    border:1px solid #ced4da;
    font-size:15px;
}

/* BIG TEXTAREA */
textarea{
    min-height:250px;
    resize:vertical;
}

.action-bar {
    margin-top: 35px;
    padding-top: 25px;
    border-top: 1px solid #ececec;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

button,
a {
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #0d6efd;
    color: #fff;
}

.btn-success {
    background: #198754;
    color: #fff;
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-secondary {
    background: #f1f3f5;
    color: #333;
    border: 1px solid #d6d9dd;
}

.badge {
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
}

.done {
    background: #e6f4ea;
    color: #1e7e34;
}

.pending {
    background: #fdecea;
    color: #c82333;
}
</style>

<div class="container <?= $mode=='edit'?'edit-mode-wrapper':'view-mode-wrapper' ?>">

    <h2>Record #<?= $record_id ?></h2>

    <div style="margin:10px 0 20px;">
        <a href="View_Records.php" class="btn-secondary">← Back to Records</a>
        <span class="badge <?= $record['is_done']?'done':'pending' ?>">
            <?= $record['is_done']?'DONE':'PENDING' ?>
        </span>
    </div>

    <div class="card <?= $mode=='edit'?'edit-mode':'view-mode' ?>">

        <form method="POST">
            <div class="form-grid">

                <?php while($field=mysqli_fetch_assoc($fields_query)):

$fid=$field['id'];
$type=$field['field_type'];
$fname=$field['field_name'];
$required=$field['required'];
$value=$values[$fid]??'';
?>

                <div class="field-block">
                    <label><?= $fname ?></label>

                    <?php if($mode=='edit'): ?>

                    <?php if(in_array($type,['text','email','number','date'])): ?>
                    <input type="<?= $type ?>" name="<?= $fid ?>" value="<?= htmlspecialchars($value) ?>"
                        <?= $required?'required':'' ?>>
                    <?php elseif($type=='textarea'): ?>
                    <textarea name="<?= $fid ?>"
                        <?= $required?'required':'' ?>><?= htmlspecialchars($value) ?></textarea>
                    <?php elseif($type=='select'): ?>
                    <select name="<?= $fid ?>">
                        <?php
$opt=mysqli_query($conn,"SELECT option_value FROM field_options WHERE field_id=$fid");
while($o=mysqli_fetch_assoc($opt)):
?>
                        <option value="<?= $o['option_value'] ?>" <?= $value==$o['option_value']?'selected':'' ?>>
                            <?= $o['option_value'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="field-value"><?= $value ?: '-' ?></div>
                    <?php endif; ?>

                </div>

                <?php endwhile; ?>

            </div>

            <div class="action-bar">

                <?php if($mode=='edit'): ?>
                <button type="submit" name="update_record" class="btn-success">Update Record</button>
                <a href="single_record.php?id=<?= $record_id ?>" class="btn-secondary">Cancel</a>
                <?php else: ?>
                <a href="single_record.php?id=<?= $record_id ?>&mode=edit" class="btn-primary">Edit Record</a>

                <button type="submit" name="toggle_done" class="btn-warning">
                    <?= $record['is_done']?'Mark as Undone':'Mark as Done' ?>
                </button>

                <a href="?id=<?= $record_id ?>&export=csv" class="btn-secondary">CSV</a>
                <a href="?id=<?= $record_id ?>&export=excel" class="btn-secondary">Excel</a>
                <a href="?id=<?= $record_id ?>&export=pdf" class="btn-secondary">PDF</a>
                <?php endif; ?>

            </div>

        </form>
    </div>
</div>