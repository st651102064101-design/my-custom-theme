<?php
/**
 * Title: Bootstrap Alerts (แจ้งเตือน)
 * Slug: my-custom-theme/bootstrap-alerts
 * Categories: my-special-design
 * Description: กล่องแจ้งเตือน Bootstrap หลายสี พร้อมปุ่มปิด
 */
?>
<!-- wp:html -->
<div class="container my-4">
    <div class="alert alert-primary alert-dismissible fade show" role="alert">
        <strong>📌 Primary!</strong> This is a primary alert — check it out!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>✅ Success!</strong> This is a success alert — well done!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>⚠️ Warning!</strong> This is a warning alert — be careful!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>❌ Danger!</strong> This is a danger alert — something went wrong!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>ℹ️ Info!</strong> This is an info alert — here's some information.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<!-- /wp:html -->
