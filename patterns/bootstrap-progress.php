<?php
/**
 * Title: Bootstrap Badge & Progress (แถบความคืบหน้า)
 * Slug: my-custom-theme/bootstrap-progress
 * Categories: my-special-design
 * Description: Progress Bars และ Badges หลากหลายรูปแบบ
 */
?>
<!-- wp:html -->
<div class="container my-5">
    <!-- Progress Bars -->
    <h5 class="mb-3">Progress Bars</h5>
    <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
            <span>Project Progress</span>
            <span>75%</span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
        </div>
    </div>
    <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
            <span>Sales Target</span>
            <span>50%</span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">50%</div>
        </div>
    </div>
    <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
            <span>Customer Satisfaction</span>
            <span>90%</span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-info" role="progressbar" style="width: 90%;" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100">90%</div>
        </div>
    </div>

    <!-- Badges -->
    <h5 class="mt-5 mb-3">Badges</h5>
    <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-primary fs-6">Primary</span>
        <span class="badge bg-secondary fs-6">Secondary</span>
        <span class="badge bg-success fs-6">Success</span>
        <span class="badge bg-danger fs-6">Danger</span>
        <span class="badge bg-warning text-dark fs-6">Warning</span>
        <span class="badge bg-info fs-6">Info</span>
        <span class="badge rounded-pill bg-primary fs-6">Pill Badge</span>
        <span class="badge rounded-pill bg-danger fs-6">99+</span>
    </div>
</div>
<!-- /wp:html -->
