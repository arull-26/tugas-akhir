<?php
// Pastikan file ini di-include setelah auth_check.php dan config.php
// Mendukung filter tanggal, pagination, dan ekspor CSV melalui riwayat_export.php

// Ambil parameter filter/pagination dari query string
$per_page_opts = [10,25,50];
$per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'],$per_page_opts) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : '';
$to = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : '';

// Bangun klausa WHERE dengan sanitasi sederhana
$where_sql = "user_id = " . intval($user_id);
if ($from) {
    $from_esc = $conn->real_escape_string($from);
    $where_sql .= " AND tanggal >= '" . $from_esc . "'";
}
if ($to) {
    $to_esc = $conn->real_escape_string($to);
    $where_sql .= " AND tanggal <= '" . $to_esc . "'";
}

// Hitung total untuk pagination
$count_q = $conn->query("SELECT COUNT(*) as total FROM absensi WHERE $where_sql");
$total = 0;
if ($count_q) {
    $total = (int) $count_q->fetch_assoc()['total'];
}
$total_pages = $total > 0 ? ceil($total / $per_page) : 1;
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$riwayat_q = $conn->query("SELECT tanggal, jam_masuk, jam_pulang, status FROM absensi WHERE $where_sql ORDER BY tanggal DESC LIMIT $offset, $per_page");

function build_query_except_page($overrides = []) {
    $params = [];
    if (isset($_GET['from'])) $params['from'] = $_GET['from'];
    if (isset($_GET['to'])) $params['to'] = $_GET['to'];
    if (isset($_GET['per_page'])) $params['per_page'] = $_GET['per_page'];
    foreach ($overrides as $k=>$v) $params[$k] = $v;
    return http_build_query($params);
}
?>

<div class="card">
    <h3>Riwayat Absensi Pribadi</h3>

    <form method="get" style="display:flex; gap:10px; align-items:center; margin-bottom:12px; flex-wrap:wrap;">
        <label>From: <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
        <label>To: <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
        <label>Per halaman:
            <select name="per_page">
                <?php foreach($per_page_opts as $opt): ?>
                    <option value="<?= $opt ?>" <?= $per_page == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn">Filter</button>
        <a class="btn btn-secondary" href="riwayat_export.php?<?= build_query_except_page() ?>">Unduh CSV</a>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($riwayat_q && $riwayat_q->num_rows > 0): ?>
                <?php while($row = $riwayat_q->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['tanggal'] ?></td>
                        <td><?= !empty($row['jam_masuk']) ? $row['jam_masuk'] : '-' ?></td>
                        <td><?= !empty($row['jam_pulang']) ? $row['jam_pulang'] : '-' ?></td>
                        <td>
                            <?php
                                $bg = '#6c757d';
                                if ($row['status'] == 'Hadir') $bg = '#28a745';
                                elseif ($row['status'] == 'Terlambat') $bg = '#ffc107';
                            ?>
                            <span class="status-badge" style="padding: 5px 10px; border-radius: 5px; color: white; background-color: <?= $bg ?>;"><?= $row['status'] ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">Tidak ada data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="display:flex; gap:8px; align-items:center; margin-top:10px; flex-wrap:wrap;">
        <span>Halaman <?= $page ?> / <?= $total_pages ?> (Total: <?= $total ?>)</span>
        <?php if ($page > 1): ?>
            <a class="btn" href="?<?= build_query_except_page(['page'=> $page-1]) ?>">&laquo; Prev</a>
        <?php endif; ?>
        <?php if ($page < $total_pages): ?>
            <a class="btn" href="?<?= build_query_except_page(['page'=> $page+1]) ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>