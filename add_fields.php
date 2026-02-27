<?php
include 'includes/db.php';
// include 'includes/db.php';
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

/* 🔒 ADMIN ONLY ACCESS */
if($_SESSION['role'] !== 'admin'){
    echo "<div style='padding:20px;font-family:Segoe UI;'>
            <h3 style='color:red;'>Access Denied</h3>
            <p>You do not have permission to access this page.</p>
          </div>";
    exit;
}


/* =====================================================
   HELPER FUNCTION – SAVE SELECT OPTIONS
===================================================== */
function saveOptions($conn, $field_id, $optionsText){
    $options = preg_split('/[\r\n,]+/', $optionsText);
    foreach($options as $option){
        $option = trim($option);
        if($option !== ''){
            $stmt = $conn->prepare("INSERT INTO field_options (field_id, option_value) VALUES (?,?)");
            $stmt->bind_param("is", $field_id, $option);
            $stmt->execute();
        }
    }
}

/* =====================================================
   ACTION HANDLERS
===================================================== */
if(isset($_GET['toggle'])){
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE fields SET required = 1 - required WHERE id = $id");
    header("Location: add_fields.php"); exit;
}

if(isset($_GET['status'])){
    $id = intval($_GET['status']);
    $conn->query("UPDATE fields SET status = 1 - status WHERE id = $id");
    header("Location: add_fields.php"); exit;
}

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM fields WHERE id = $id");
    header("Location: add_fields.php"); exit;
}

/* =====================================================
   EDIT FETCH
===================================================== */
$editData = null;
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM fields WHERE id = $id");
    $editData = $result->fetch_assoc();
}

/* =====================================================
   ADD / UPDATE
===================================================== */
if(isset($_POST['save_field'])){

    $field_name = trim($_POST['field_name']);
    $field_type = strtolower($_POST['field_type']);
    $required   = isset($_POST['required']) ? 1 : 0;

    if(!empty($_POST['field_id'])){

        $id = intval($_POST['field_id']);

        $stmt = $conn->prepare("UPDATE fields SET field_name=?, field_type=?, required=? WHERE id=?");
        $stmt->bind_param("ssii", $field_name, $field_type, $required, $id);
        $stmt->execute();

        $conn->query("DELETE FROM field_options WHERE field_id=$id");

        if($field_type === 'select' && !empty($_POST['options'])){
            saveOptions($conn, $id, $_POST['options']);
        }

    } else {

        $stmt = $conn->prepare("INSERT INTO fields (field_name, field_type, required) VALUES (?,?,?)");
        $stmt->bind_param("ssi", $field_name, $field_type, $required);
        $stmt->execute();

        $field_id = $stmt->insert_id;

        if($field_type === 'select' && !empty($_POST['options'])){
            saveOptions($conn, $field_id, $_POST['options']);
        }
    }

    header("Location: add_fields.php");
    exit;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
.card {
    background:#fff;
    padding:30px;
    border-radius:8px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    margin-bottom:30px;
}
.page-title {
    font-size:24px;
    font-weight:600;
    margin-bottom:25px;
}
.form-group {
    display:flex;
    align-items:center;
    margin-bottom:18px;
}
.form-group label {
    width:170px;
    font-weight:500;
}
.form-group input,
.form-group select,
.form-group textarea {
    flex:1;
    padding:10px 12px;
    border-radius:6px;
    border:1px solid #d1d5db;
    background:#f9fafb;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color:#2563eb;
    background:#fff;
    outline:none;
}
.btn-primary {
    background:linear-gradient(to right,#1e3a8a,#2563eb);
    color:#fff;
    padding:10px 25px;
    border-radius:6px;
    border:none;
    cursor:pointer;
}
.search-box {
    padding:8px 12px;
    border:1px solid #d1d5db;
    border-radius:6px;
    background:#f9fafb;
    width:220px;
}
table {
    width:100%;
    border-collapse:collapse;
}
table th {
    background:#f3f4f6;
    padding:12px;
    text-align:left;
}
table td {
    padding:12px;
    border-top:1px solid #e5e7eb;
}
table tbody tr:hover {
    background:#f3f4f6;
}
.btn-action {
    padding:4px 10px;
    border-radius:4px;
    font-size:12px;
    text-decoration:none;
    margin-right:6px;
    color:#fff;
}
.edit{ background:#3b82f6; }
.toggle{ background:#6b7280; }
.delete{ background:#ef4444; }
.status{ background:#10b981; }

.badge {
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:500;
    color:#fff;
}
.badge-text{ background:#3b82f6; }
.badge-number{ background:#10b981; }
.badge-email{ background:#8b5cf6; }
.badge-date{ background:#f59e0b; }
.badge-textarea{ background:#6366f1; }
.badge-select{ background:#ec4899; }

.switch {
    position:relative;
    display:inline-block;
    width:50px;
    height:26px;
}
.switch input { opacity:0; width:0; height:0; }
.slider {
    position:absolute;
    cursor:pointer;
    background:#ccc;
    border-radius:34px;
    top:0; left:0; right:0; bottom:0;
    transition:.3s;
    width: 50px;
}
.slider:before {
    position:absolute;
    content:"";
    height:20px;
    width:20px;
    left:3px;
    bottom:3px;
    background:white;
    border-radius:50%;
    transition:.3s;
}
input:checked+.slider { background:#2563eb; }
input:checked+.slider:before { transform:translateX(24px); }

#options_box { transition:all .3s ease; }
</style>

<!-- ================= FORM ================= -->

<div class="card">
    <div class="page-title"><?= $editData ? 'Edit Field' : 'Add a Field'; ?></div>

    <form method="POST">
        <input type="hidden" name="field_id" value="<?= $editData['id'] ?? '' ?>">

        <div class="form-group">
            <label>Field Name:</label>
            <input type="text" name="field_name"
                value="<?= htmlspecialchars($editData['field_name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Field Type:</label>
            <select name="field_type" id="field_type" required>
                <option value="">Select Type</option>
                <?php
                $types = ['text','number','email','date','textarea','select'];
                foreach($types as $type){
                    $selected = ($editData && $editData['field_type']==$type) ? 'selected' : '';
                    echo "<option value='$type' $selected>".ucfirst($type)."</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group" id="options_box"
            style="display:<?= ($editData && $editData['field_type']=='select')?'flex':'none' ?>;">
            <label>Dropdown Options:</label>
            <textarea name="options"><?php
                if($editData && $editData['field_type']=='select'){
                    $opts = $conn->query("SELECT option_value FROM field_options WHERE field_id=".$editData['id']);
                    $arr = [];
                    while($o = $opts->fetch_assoc()){ $arr[] = $o['option_value']; }
                    echo implode(',', $arr);
                }
            ?></textarea>
        </div>

        <div class="form-group">
            <label>Make Required:</label>
            <label class="switch">
                <input type="checkbox" name="required"
                    <?= ($editData && $editData['required']) ? 'checked':'' ?>>
                <span class="slider"></span>
            </label>
        </div>

        <button type="submit" name="save_field" class="btn-primary">
            <?= $editData ? 'Update Field' : 'Save Field'; ?>
        </button>
    </form>
</div>

<!-- ================= TABLE ================= -->

<div class="card">

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
    <div class="page-title" style="margin:0;">Existing Fields</div>
    <input type="text" id="searchInput" placeholder="Search fields..." class="search-box">
</div>

<table>
<thead>
<tr>
    <th>Id</th>
    <th>Created</th>
    <th>Name</th>
    <th>Type</th>
    <th>Required</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
$result = $conn->query("SELECT * FROM fields ORDER BY id DESC");
while($row = $result->fetch_assoc()):
?>
<tr>
    <td><?= $i++; ?></td>
    <td><?= date('d M Y', strtotime($row['created_at'])); ?></td>
    <td><?= htmlspecialchars($row['field_name']); ?></td>
    <td><span class="badge badge-<?= $row['field_type']; ?>">
        <?= ucfirst($row['field_type']); ?></span></td>
    <td><?= $row['required'] ? 'Yes':'No'; ?></td>
    <td><?= $row['status'] ? 'Active':'Inactive'; ?></td>
    <td>
        <a class="btn-action edit" href="?edit=<?= $row['id']; ?>">Edit</a>
        <a class="btn-action toggle" href="?toggle=<?= $row['id']; ?>">
            <?= $row['required'] ? 'Optional':'Required'; ?>
        </a>
        <a class="btn-action status" href="?status=<?= $row['id']; ?>">
            <?= $row['status'] ? 'Deactivate':'Activate'; ?>
        </a>
        <!-- <a class="btn-action delete" href="?delete=<?= $row['id']; ?>"
           onclick="return confirm('Delete this field?')">Delete</a> -->
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

<script>
document.getElementById('field_type').addEventListener('change', function(){
    document.getElementById('options_box').style.display =
        this.value === 'select' ? 'flex' : 'none';
});

document.getElementById("searchInput").addEventListener("keyup", function(){
    let value = this.value.toLowerCase();
    document.querySelectorAll("tbody tr").forEach(row=>{
        row.style.display = row.textContent.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>