<?php
include 'includes/db.php';


/* ================= GET PARAMETERS ================= */
$type    = $_GET['type'] ?? 'csv';
$search  = $_GET['search'] ?? '';
$from    = $_GET['from'] ?? '';
$to      = $_GET['to'] ?? '';
$sort    = $_GET['sort'] ?? 'created_at';
$order   = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$filters = $_GET['filters'] ?? [];

/* ================= FETCH FIRST 5 ACTIVE FIELDS ================= */
$fields = [];
$fieldQuery = $conn->query("SELECT * FROM fields WHERE status=1 ORDER BY id ASC LIMIT 5");
while ($row = $fieldQuery->fetch_assoc()) {
    $fields[] = $row;
}

/* ================= BUILD QUERY ================= */
$sql = "SELECT r.*";

foreach ($fields as $index => $field) {
    $alias = "f$index";
    $sql .= ", $alias.value AS sort_$index";
}

$sql .= " FROM records r";

foreach ($fields as $index => $field) {
    $fieldId = $field['id'];
    $alias = "f$index";
    $sql .= " LEFT JOIN record_values $alias
              ON r.id = $alias.record_id
              AND $alias.field_id = $fieldId";
}

$sql .= " WHERE 1=1";

if ($from) $sql .= " AND DATE(r.created_at) >= '$from'";
if ($to)   $sql .= " AND DATE(r.created_at) <= '$to'";

if ($search) {
    $safe = $conn->real_escape_string($search);
    $sql .= " AND r.id IN (
        SELECT record_id FROM record_values
        WHERE value LIKE '%$safe%'
    )";
}

if (!empty($filters)) {
    foreach ($filters as $fieldId => $value) {
        $fieldId = intval($fieldId);
        $valueSafe = $conn->real_escape_string($value);
        $sql .= " AND r.id IN (
            SELECT record_id FROM record_values
            WHERE field_id = $fieldId
            AND value = '$valueSafe'
        )";
    }
}

if ($sort === 'created_at') {
    $sql .= " ORDER BY r.created_at $order";
} else {
    $sortIndex = intval(str_replace('field_', '', $sort));
    $sql .= " ORDER BY sort_$sortIndex $order";
}

$query = $conn->query($sql);

$records = [];
while ($row = $query->fetch_assoc()) {
    $records[$row['id']] = $row;
}

/* ================= FETCH VALUES ================= */
$valuesMap = [];
if (!empty($records)) {
    $ids = implode(",", array_keys($records));
    $vQuery = $conn->query("
        SELECT record_id, field_id, value 
        FROM record_values 
        WHERE record_id IN ($ids)
    ");
    while ($val = $vQuery->fetch_assoc()) {
        $valuesMap[$val['record_id']][$val['field_id']] = $val['value'];
    }
}

/* ================= EXPORT CSV ================= */
if ($type === 'csv') {

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=records.csv');

    $output = fopen('php://output', 'w');

    $header = ['S.No', 'Created'];
    foreach ($fields as $field) {
        $header[] = $field['field_name'];
    }
    $header[] = 'Status';

    fputcsv($output, $header);

    $counter = 1;

    foreach ($records as $id => $record) {

        $row = [$counter++, $record['created_at']];

        foreach ($fields as $field) {
            $row[] = $valuesMap[$id][$field['id']] ?? '';
        }

        $row[] = $record['is_done'] ? 'Done' : 'Pending';

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/* ================= EXPORT EXCEL ================= */
if ($type === 'excel') {

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename=records.xls');

    echo "S.No\tCreated\t";

    foreach ($fields as $field) {
        echo $field['field_name'] . "\t";
    }

    echo "Status\n";

    $counter = 1;

    foreach ($records as $id => $record) {

        echo $counter++ . "\t";
        echo $record['created_at'] . "\t";

        foreach ($fields as $field) {
            echo ($valuesMap[$id][$field['id']] ?? '') . "\t";
        }

        echo ($record['is_done'] ? 'Done' : 'Pending');
        echo "\n";
    }

    exit;
}


/* ================= EXPORT PDF ================= */
if ($type === 'pdf') {

    require(__DIR__ . '/includes/FPDF/fpdf.php');

    class PDF extends FPDF {

        function Header()
        {
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10,'Records Report',0,1,'C');
            $this->Ln(3);
        }

        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
        }

        function Row($data, $widths)
        {
            $nb = 0;
            for($i=0;$i<count($data);$i++){
                $nb = max($nb, $this->NbLines($widths[$i], $data[$i]));
            }

            $h = 6 * $nb;

            if($this->GetY() + $h > $this->PageBreakTrigger)
                $this->AddPage();

            for($i=0;$i<count($data);$i++)
            {
                $w = $widths[$i];
                $x = $this->GetX();
                $y = $this->GetY();

                $this->Rect($x,$y,$w,$h);
                $this->MultiCell($w,6,$data[$i],0,'L');
                $this->SetXY($x+$w,$y);
            }
            $this->Ln($h);
        }

        function NbLines($w, $txt)
        {
            $cw = &$this->CurrentFont['cw'];
            if($w==0)
                $w = $this->w-$this->rMargin-$this->x;
            $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
            $s = str_replace("\r",'',$txt);
            $nb = strlen($s);
            if($nb>0 && $s[$nb-1]=="\n")
                $nb--;
            $sep = -1;
            $i = 0;
            $j = 0;
            $l = 0;
            $nl = 1;
            while($i<$nb)
            {
                $c = $s[$i];
                if($c=="\n")
                {
                    $i++;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                    continue;
                }
                if($c==' ')
                    $sep = $i;
                $l += $cw[$c];
                if($l>$wmax)
                {
                    if($sep==-1)
                    {
                        if($i==$j)
                            $i++;
                    }
                    else
                        $i = $sep+1;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                }
                else
                    $i++;
            }
            return $nl;
        }
    }

    // 🔥 Portrait Mode
    $pdf = new PDF('P','mm','A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',9);

    /* ================= CALCULATE WIDTHS ================= */

    $totalWidth = 190; // A4 portrait usable width

    $widths = [];

    // Fixed columns
    $widths[] = 12;  // S.No
    $widths[] = 28;  // Created

    $fixedWidth = 12 + 28 + 25; // S.No + Created + Status
    $remainingWidth = $totalWidth - $fixedWidth;

    $fieldCount = count($fields);

    // Distribute remaining width among dynamic fields
    $dynamicWidth = floor($remainingWidth / max($fieldCount,1));

    foreach ($fields as $field) {
        if($field['field_type'] == 'textarea'){
            $widths[] = $dynamicWidth + 10; // slightly bigger
        } else {
            $widths[] = $dynamicWidth;
        }
    }

    $widths[] = 25; // Status

    /* ================= HEADER ================= */

    $pdf->SetFillColor(230,230,230);

    $headers = ['S.No','Created'];
    foreach ($fields as $field) {
        $headers[] = $field['field_name'];
    }
    $headers[] = 'Status';

    for($i=0;$i<count($headers);$i++){
        $pdf->Cell($widths[$i],8,$headers[$i],1,0,'L',true);
    }

    $pdf->Ln();
    $pdf->SetFont('Arial','',8);

    $counter = 1;

    foreach ($records as $id => $record) {

        $row = [];
        $row[] = $counter++;
        $row[] = date('d M Y H:i', strtotime($record['created_at']));

        foreach ($fields as $field) {
            $row[] = $valuesMap[$id][$field['id']] ?? '';
        }

        $row[] = $record['is_done'] ? 'Done' : 'Pending';

        $pdf->Row($row, $widths);
    }

    $pdf->Output('D','records_report.pdf');
    exit;
}