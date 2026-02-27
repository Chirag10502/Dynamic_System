<?php
include 'includes/db.php';

if(session_status() === PHP_SESSION_NONE){
    session_start();
}
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

/* ============================================
   FETCH ACTIVE FIELDS
============================================ */
$fields = [];
$fieldQuery = $conn->query("SELECT * FROM fields WHERE status = 1 ORDER BY id ASC");

while ($row = $fieldQuery->fetch_assoc()) {
    $fields[] = $row;
}

/* ============================================
   FETCH ALL DROPDOWN OPTIONS (OPTIMIZED)
============================================ */
$optionsMap = [];
$optionQuery = $conn->query("SELECT * FROM field_options ORDER BY id ASC");

while ($opt = $optionQuery->fetch_assoc()) {
    $optionsMap[$opt['field_id']][] = $opt;
}

$errors = [];

/* ============================================
   HANDLE FORM SUBMISSION
============================================ */
if (isset($_POST['save_record'])) {

    // Validate Required Fields
    foreach ($fields as $field) {
        $field_id = $field['id'];
        $field_name = $field['field_name'];

        if ($field['required'] && empty($_POST["field_$field_id"])) {
            $errors[] = "$field_name is required.";
        }
    }

    if (empty($errors)) {

        // 1️⃣ Insert into records
        $conn->query("INSERT INTO records () VALUES ()");
        $record_id = $conn->insert_id;

        // 2️⃣ Insert into record_values
        $stmt = $conn->prepare("INSERT INTO record_values (record_id, field_id, value) VALUES (?, ?, ?)");

        foreach ($fields as $field) {

            $field_id = $field['id'];
            $value = isset($_POST["field_$field_id"])
                ? trim($_POST["field_$field_id"])
                : $field['default_value'];

            $stmt->bind_param("iis", $record_id, $field_id, $value);
            $stmt->execute();
        }

        // 3️⃣ Redirect
        header("Location: view_records.php");
        exit;
    }
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
.error-box {
    background:#fee2e2;
    color:#b91c1c;
    padding:12px;
    border-radius:6px;
    margin-bottom:15px;
}
</style>

<div class="card">

    <div class="page-title">Add New Record</div>

    <?php if (empty($fields)): ?>
        <div class="error-box">
            No active fields found. Please create fields first.
        </div>
    <?php else: ?>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $err) {
                echo "<div>" . htmlspecialchars($err) . "</div>";
            } ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <?php foreach ($fields as $field):

            $field_id = $field['id'];
            $type = $field['field_type'];
            $label = $field['field_name'];
            $required = $field['required'] ? 'required' : '';
            $default = htmlspecialchars($field['default_value'] ?? '');
        ?>

        <div class="form-group">
            <label><?= htmlspecialchars($label); ?>:</label>

            <?php if ($type === 'text'): ?>
                <input type="text" name="field_<?= $field_id; ?>" value="<?= $default; ?>" <?= $required; ?>>

            <?php elseif ($type === 'number'): ?>
                <input type="number" name="field_<?= $field_id; ?>" value="<?= $default; ?>" <?= $required; ?>>

            <?php elseif ($type === 'email'): ?>
                <input type="email" name="field_<?= $field_id; ?>" value="<?= $default; ?>" <?= $required; ?>>

            <?php elseif ($type === 'date'): ?>
                <input type="date" name="field_<?= $field_id; ?>" value="<?= $default; ?>" <?= $required; ?>>

            <?php elseif ($type === 'textarea'): ?>
                <textarea name="field_<?= $field_id; ?>" <?= $required; ?>><?= $default; ?></textarea>

            <?php elseif ($type === 'select'): ?>
                <select name="field_<?= $field_id; ?>" <?= $required; ?>>
                    <option value="">Select</option>
                    <?php
                    if (isset($optionsMap[$field_id])):
                        foreach ($optionsMap[$field_id] as $opt):
                            $selected = $opt['is_default'] ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($opt['option_value']); ?>" <?= $selected; ?>>
                            <?= htmlspecialchars($opt['option_value']); ?>
                        </option>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </select>
            <?php endif; ?>

        </div>

        <?php endforeach; ?>

        <button type="submit" name="save_record" class="btn-primary">
            Save Record
        </button>

    </form>

    <?php endif; ?>

</div>