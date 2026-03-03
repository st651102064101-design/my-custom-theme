<?php
/**
 * Title: Bootstrap Tabs (แท็บ)
 * Slug: my-custom-theme/bootstrap-tabs
 * Categories: my-special-design
 * Description: แท็บ Bootstrap สลับเนื้อหาได้โดยไม่ต้องโหลดหน้าใหม่
 */
?>
<!-- wp:html -->
<div class="container my-5">
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab" aria-controls="tab1" aria-selected="true">Tab 1</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" aria-controls="tab2" aria-selected="false">Tab 2</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab" aria-controls="tab3" aria-selected="false">Tab 3</button>
        </li>
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-4" id="myTabContent">
        <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
            <h4>Tab 1 Content</h4>
            <p>This is the content for the first tab. You can add any HTML content here including images, text, forms, or other Bootstrap components.</p>
        </div>
        <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
            <h4>Tab 2 Content</h4>
            <p>This is the content for the second tab. Each tab can have completely different content and layout.</p>
        </div>
        <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
            <h4>Tab 3 Content</h4>
            <p>This is the content for the third tab. Tabs are great for organizing related content without overwhelming the page.</p>
        </div>
    </div>
</div>
<!-- /wp:html -->
