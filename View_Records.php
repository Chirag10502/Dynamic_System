<?php
include 'includes/db.php';



session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

/* ================= FETCH FIRST 5 ACTIVE FIELDS ================= */
$fields = [];
$fieldQuery = $conn->query("SELECT * FROM fields WHERE status=1 ORDER BY id ASC LIMIT 5");
while ($row = $fieldQuery->fetch_assoc()) {
    $fields[] = $row;
}

/* ================= FETCH SELECT FIELDS FOR FILTER ================= */
$selectFields = [];
$filterOptionsMap = [];

$sfQuery = $conn->query("SELECT * FROM fields WHERE status=1 AND field_type='select'");
while ($row = $sfQuery->fetch_assoc()) {
    $selectFields[] = $row;
}

$optQuery = $conn->query("SELECT * FROM field_options ORDER BY id ASC");
while ($opt = $optQuery->fetch_assoc()) {
    $filterOptionsMap[$opt['field_id']][] = $opt['option_value'];
}

// /* ================= DELETE ================= */
// if(isset($_GET['delete'])){
//     $id = intval($_GET['delete']);
//     $conn->query("DELETE FROM records WHERE id=$id");
//     exit;
// }

/* ================= TOGGLE DONE ================= */
if(isset($_GET['toggle_done'])){
    $id = intval($_GET['toggle_done']);
    $conn->query("UPDATE records SET is_done = 1 - is_done WHERE id=$id");
    exit;
}

/* ================= AJAX DATA ================= */
if(isset($_GET['ajax'])){

    $search  = $_GET['search'] ?? '';
    $from    = $_GET['from'] ?? '';
    $to      = $_GET['to'] ?? '';
    $sort    = $_GET['sort'] ?? 'created_at';
    $order   = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    $filters = $_GET['filters'] ?? [];

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

    if($from) $sql .= " AND DATE(r.created_at) >= '$from'";
    if($to)   $sql .= " AND DATE(r.created_at) <= '$to'";

    if($search){
        $safe = $conn->real_escape_string($search);
        $sql .= " AND r.id IN (
            SELECT record_id FROM record_values
            WHERE value LIKE '%$safe%'
        )";
    }

    if(!empty($filters)){
        foreach($filters as $fieldId => $value){
            $fieldId = intval($fieldId);
            $valueSafe = $conn->real_escape_string($value);
            $sql .= " AND r.id IN (
                SELECT record_id FROM record_values
                WHERE field_id = $fieldId
                AND value = '$valueSafe'
            )";
        }
    }

    if($sort === 'created_at'){
        $sql .= " ORDER BY r.created_at $order";
    } else {
        $sortIndex = intval(str_replace('field_', '', $sort));
        $sql .= " ORDER BY sort_$sortIndex $order";
    }

    $query = $conn->query($sql);

    $records = [];
    while($row = $query->fetch_assoc()){
        $records[$row['id']] = $row;
    }

    $valuesMap = [];
    if(!empty($records)){
        $ids = implode(",", array_keys($records));
        $vQuery = $conn->query("SELECT record_id, field_id, value FROM record_values WHERE record_id IN ($ids)");
        while($val = $vQuery->fetch_assoc()){
            $valuesMap[$val['record_id']][$val['field_id']] = $val['value'];
        }
    }

    $counter = 1;

    foreach($records as $id => $record){

        echo "<tr>";
        echo "<td>".$counter++."</td>";
        echo "<td>".date('d M Y', strtotime($record['created_at']))."</td>";

        foreach($fields as $field){
            $value = $valuesMap[$id][$field['id']] ?? '-';
            $value = htmlspecialchars($value);

            if(strlen($value) > 60){
                echo "<td class='long-text'>".substr($value,0)."</td>";
            } else {
                echo "<td>".$value."</td>";
            }
        }

        echo "<td>".($record['is_done']
            ? "<span class='badge-done'>Done</span>"
            : "<span class='badge-pending'>Pending</span>")."</td>";

        echo "<td style='white-space:nowrap;'>
            <a href='single_record.php?id=$id' class='action-btn view'>View</a>
            <a href='single_record.php?id=$id&edit=1' class='action-btn edit'>Edit</a>
            <a href='#' onclick='toggleDone($id)' class='action-btn done-toggle'>Done</a>
           
        </td>";

        echo "</tr>";
    }

    exit;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
body {
    background: #f4f6f9;
    font-family: 'Segoe UI';
}



.card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.filter-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.filter-bar input,
.filter-bar select {
    padding: 5px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
}

.filter-btn,
.export-btn {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}

.filter-btn {
    background: #2563eb;
    color: #fff;
}

.export-btn {
    background: #16a34a;
    color: #fff;
}

.loading-indicator {
    display: none;
    color: #2563eb;
    font-weight: 600;
    font-size: 13px;
    margin-left: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: #fff;
}

th,
td {
    padding: 6px 8px;
    border: 1px solid #e5e7eb;
    vertical-align: middle;
}

th {
    background: #f3f4f6;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

tbody tr:hover {
    background: #f9fafb;
}

.long-text {
    white-space: normal;
    max-width: 300px;
}

.badge-done {
    background: #dcfce7;
    color: #166534;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.badge-pending {
    background: #e5e7eb;
    color: #374151;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.action-btn {
    padding: 3px 6px;
    font-size: 12px;
    border-radius: 4px;
    color: #fff;
    text-decoration: none;
    margin-right: 4px;
    display: inline-block;
}

.view {
    background: #2563eb;
}

.edit {
    background: #6b7280;
}

.delete {
    background: #dc2626;
}

.done-toggle {
    background: #16a34a;
}

.sort-arrow {
    font-size: 10px;
    margin-left: 4px;
}
</style>

<div class="main-container">
    <div class="card">

        <div class="filter-bar">
            <input type="date" id="from">
            <input type="date" id="to">
            <input type="text" id="search" placeholder="Search...">

            <?php foreach($selectFields as $sf): ?>
            <select class="dynamic-filter" data-field="<?= $sf['id']; ?>">
                <option value="">All <?= htmlspecialchars($sf['field_name']); ?></option>
                <?php if(isset($filterOptionsMap[$sf['id']])): ?>
                <?php foreach($filterOptionsMap[$sf['id']] as $option): ?>
                <option value="<?= htmlspecialchars($option); ?>"><?= htmlspecialchars($option); ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <?php endforeach; ?>

            <button onclick="fetchData()" class="filter-btn">Apply</button>

           
        </div>

        <div class="filter-bar">
             <button onclick="exportData('csv')" class="export-btn">CSV</button>
            <button onclick="exportData('excel')" class="export-btn">Excel</button>
            <button onclick="exportData('pdf')" class="export-btn">PDF</button>

            <span class="loading-indicator" id="exportLoading">Exporting...</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th onclick="sortTable('created_at')">Created <span class="sort-arrow" id="arrow_created_at"></span>
                    </th>
                    <?php foreach($fields as $index=>$field): ?>
                    <th onclick="sortTable('field_<?= $index ?>')">
                        <?= htmlspecialchars($field['field_name']); ?>
                        <span class="sort-arrow" id="arrow_field_<?= $index ?>"></span>
                    </th>
                    <?php endforeach; ?>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="recordTable"></tbody>
        </table>

    </div>
</div>

<script>
let currentSort = 'created_at';
let currentOrder = 'DESC';

function sortTable(column) {
    if (currentSort === column) {
        currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
    } else {
        currentSort = column;
        currentOrder = 'ASC';
    }
    updateArrows();
    fetchData();
}

function updateArrows() {
    document.querySelectorAll('.sort-arrow').forEach(el => el.innerHTML = '');
    let arrow = document.getElementById('arrow_' + currentSort);
    if (arrow) arrow.innerHTML = currentOrder === 'ASC' ? '↑' : '↓';
}

function fetchData() {
    const params = new URLSearchParams();
    params.append("ajax", 1);
    params.append("sort", currentSort);
    params.append("order", currentOrder);
    params.append("from", document.getElementById("from").value);
    params.append("to", document.getElementById("to").value);
    params.append("search", document.getElementById("search").value);

    document.querySelectorAll(".dynamic-filter").forEach(select => {
        if (select.value !== "") {
            params.append(`filters[${select.dataset.field}]`, select.value);
        }
    });

    fetch("view_records.php?" + params.toString())
        .then(res => res.text())
        .then(html => {
            document.getElementById("recordTable").innerHTML = html;
        });
}

function exportData(type) {
    document.getElementById("exportLoading").style.display = "inline";
    const params = new URLSearchParams();
    params.append("type", type);
    params.append("sort", currentSort);
    params.append("order", currentOrder);
    params.append("from", document.getElementById("from").value);
    params.append("to", document.getElementById("to").value);
    params.append("search", document.getElementById("search").value);

    document.querySelectorAll(".dynamic-filter").forEach(select => {
        if (select.value !== "") {
            params.append(`filters[${select.dataset.field}]`, select.value);
        }
    });

    setTimeout(() => {
        window.location.href = "export_records.php?" + params.toString();
        document.getElementById("exportLoading").style.display = "none";
    }, 400);
}

// function deleteRecord(id) {
//     if (confirm("Delete this record?")) {
//         fetch("view_records.php?delete=" + id).then(() => fetchData());
//     }
// }

function toggleDone(id) {
    fetch("view_records.php?toggle_done=" + id).then(() => fetchData());
}

fetchData();
</script>


