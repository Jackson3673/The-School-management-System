<div class="top-bar">
    <div>
        <!-- Breadcrumb or Page Title determined by Variable or fallback -->
        <h2 class="fw-bold m-0">
            <?= $page_title ?? 'Dashboard' ?>
        </h2>
        <p class="text-muted m-0">
            <?= $page_subtitle ?? ('Welcome back, ' . htmlspecialchars($_SESSION['fullname'] ?? 'User')) ?>
        </p>
    </div>

    <div class="d-flex align-items-center gap-4">
        <div class="search-bar">
            <i class="bi bi-search text-muted"></i>
            <input type="text" placeholder="Search...">
        </div>
        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
            style="width: 40px; height: 40px;">
            <?= strtoupper(substr($_SESSION['fullname'] ?? 'U', 0, 1)) ?>
        </div>
    </div>
</div>
