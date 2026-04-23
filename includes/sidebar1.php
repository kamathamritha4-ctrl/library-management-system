<div class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div style="display:flex; align-items:center; gap:10px;">
            <img src="../assets/trisha-logo.svg" alt="Trisha Logo" style="width:38px; height:38px; border-radius:8px; object-fit:contain; background:white;">
            <h3 style="line-height:1.1; font-size:16px;">Trisha<br><span style="font-size:12px; font-weight:500; opacity:.85;">Library Admin</span></h3>
        </div>
        <button class="collapse-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <a href="dashboard.php">
        <i class="fas fa-chart-line"></i>
        <span>Dashboard</span>
    </a>

    <a href="manage_books.php">
        <i class="fas fa-book"></i>
        <span>Manage Books</span>
    </a>

    <a href="add_book.php">
        <i class="fas fa-plus"></i>
        <span>Add Book</span>
    </a>

    <a href="issue_book.php">
        <i class="fas fa-hand-holding"></i>
        <span>Issue Book</span>
    </a>

    <a href="issued_book.php">
        <i class="fas fa-clipboard-list"></i>
        <span>Issued Books</span>
    </a>

    <a href="manage_holidays.php">
        <i class="fas fa-calendar-days"></i>
        <span>Holidays</span>
    </a>

    <a href="export_books.php">
        <i class="fas fa-file-export"></i>
        <span>Export Books</span>
    </a>

    <a href="../logout.php">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
}
</script>
