<?php
global $conn;
require_role('admin');

// ── AJAX autocomplete endpoint ────────────────────────────────
if (isset($_GET['autocomplete'])) {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    if ($q === '') { echo '[]'; exit(); }

    $safe = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $q);
    $like = "%$safe%";
    $stmt = mysqli_prepare($conn,
        "SELECT v.vendor_id, v.name, v.phone, s.shop_name
         FROM vendors v JOIN shops s ON v.shop_id = s.shop_id
         WHERE v.name LIKE ? OR v.phone LIKE ?
         LIMIT 8");
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $out = [];
    while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
    mysqli_stmt_close($stmt);
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit();
}

// ── POST handler ──────────────────────────────────────────────
$error = '';
$prefill_vendor = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;
$prefill_name   = '';

if ($prefill_vendor > 0) {
    $pf = mysqli_prepare($conn, "SELECT name FROM vendors WHERE vendor_id = ?");
    mysqli_stmt_bind_param($pf, 'i', $prefill_vendor);
    mysqli_stmt_execute($pf);
    $pfr = mysqli_fetch_assoc(mysqli_stmt_get_result($pf));
    $prefill_name = $pfr['name'] ?? '';
    mysqli_stmt_close($pf);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $vendor_id = isset($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : 0;
    $amount    = trim($_POST['amount']         ?? '');
    $method    = trim($_POST['payment_method'] ?? '');
    $date      = trim($_POST['payment_date']   ?? '');
    $note      = trim($_POST['note']           ?? '');

    $allowed_methods = ['Cash', 'Bank Transfer', 'Mobile Banking'];

    if ($vendor_id <= 0 || $amount === '' || !in_array($method, $allowed_methods, true) || $date === '') {
        $error = 'All required fields must be filled correctly.';
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $error = 'Amount must be a positive number.';
    } elseif (!strtotime($date)) {
        $error = 'Invalid date.';
    } else {
        $date = date('Y-m-d', strtotime($date));

        // Verify vendor exists
        $chk = mysqli_prepare($conn, "SELECT vendor_id FROM vendors WHERE vendor_id = ?");
        mysqli_stmt_bind_param($chk, 'i', $vendor_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $vendor_ok = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);

        if (!$vendor_ok) {
            $error = 'Selected vendor does not exist. Please pick from the autocomplete list.';
        } else {
            $amt = (float)$amount;
            $stmt = mysqli_prepare($conn,
                "INSERT INTO payments (vendor_id, amount, payment_method, payment_date, note) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'idsss', $vendor_id, $amt, $method, $date, $note);
            if (mysqli_stmt_execute($stmt)) {
                flash_set('success', number_format($amt, 2) . ' TK payment recorded.');
                header('Location: index.php?page=rent_record'); exit();
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$page_title = 'Add Payment';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Add <span>Payment</span></h1>

<div class="card form-box" style="width:520px;">
  <?php if ($error): ?>
    <div class="alert alert-error"><span>✕</span> <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" id="payment-form" data-validate>
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label>Vendor <span style="color:rgba(255,255,255,0.35);font-weight:400;">(type name or phone)</span></label>
      <div class="autocomplete-wrap">
        <input type="text" id="vendor-search" autocomplete="off"
               placeholder="Start typing…"
               value="<?php echo htmlspecialchars($prefill_name); ?>">
        <div class="autocomplete-list" id="vendor-ac"></div>
      </div>
      <input type="hidden" name="vendor_id" id="vendor_id_hidden"
             value="<?php echo $prefill_vendor ?: ''; ?>">
      <div class="vendor-hint" id="vendor-hint">
        <?php if ($prefill_name) echo '✓ ' . htmlspecialchars($prefill_name); ?>
      </div>
    </div>

    <div class="form-group">
      <label>Amount (TK)</label>
      <input type="number" name="amount" placeholder="e.g. 5000" step="0.01" min="0.01" required
             value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label>Payment Method</label>
      <select name="payment_method" required>
        <?php foreach (['Cash','Bank Transfer','Mobile Banking'] as $m): ?>
          <option value="<?php echo $m; ?>"
            <?php if (($_POST['payment_method'] ?? '') === $m) echo 'selected'; ?>>
            <?php echo $m; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Payment Date</label>
      <input type="date" name="payment_date" required
             value="<?php echo htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d')); ?>">
    </div>

    <div class="form-group">
      <label>Note <span style="color:rgba(255,255,255,0.35);font-weight:400;">(optional)</span></label>
      <input type="text" name="note" placeholder="e.g. January rent"
             value="<?php echo htmlspecialchars($_POST['note'] ?? ''); ?>">
    </div>

    <button type="submit" class="btn btn-primary btn-full">Record Payment →</button>
  </form>

  <p class="form-footer"><a href="index.php?page=rent_record">← Rent Records</a></p>
</div>

<script>
(function () {
  const inp    = document.getElementById('vendor-search');
  const ac     = document.getElementById('vendor-ac');
  const hidden = document.getElementById('vendor_id_hidden');
  const hint   = document.getElementById('vendor-hint');
  let timer;

  inp.addEventListener('input', function () {
    clearTimeout(timer);
    hidden.value = '';
    hint.textContent = '';
    const q = this.value.trim();
    if (q.length < 1) { ac.innerHTML = ''; ac.style.display = 'none'; return; }

    timer = setTimeout(function () {
      fetch('index.php?page=add_payment&autocomplete=1&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
          ac.innerHTML = '';
          if (!data.length) { ac.style.display = 'none'; return; }
          ac.style.display = 'block';
          data.forEach(v => {
            const d = document.createElement('div');
            d.className = 'ac-item';
            d.innerHTML = `<div class="ac-name">${v.name}</div>
                           <div class="ac-meta">ID: ${v.vendor_id} &bull; ${v.phone} &bull; ${v.shop_name}</div>`;
            d.addEventListener('click', function () {
              inp.value    = v.name;
              hidden.value = v.vendor_id;
              hint.textContent = '✓ ' + v.shop_name + ' — ' + v.phone;
              ac.innerHTML = ''; ac.style.display = 'none';
            });
            ac.appendChild(d);
          });
        })
        .catch(() => { ac.style.display = 'none'; });
    }, 200);
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('.autocomplete-wrap')) { ac.style.display = 'none'; }
  });

  // Validate vendor selected before submit
  document.getElementById('payment-form').addEventListener('submit', function (e) {
    if (!hidden.value) {
      e.preventDefault();
      inp.classList.add('input-error');
      hint.style.color = '#fca5a5';
      hint.textContent = '⚠ Please select a vendor from the suggestions.';
      inp.focus();
    }
  });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
