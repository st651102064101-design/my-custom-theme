<?php
/**
 * Title: Bootstrap Modal (Popup)
 * Slug: my-custom-theme/bootstrap-modal
 * Categories: my-special-design
 * Description: ปุ่มเปิด Modal Popup พร้อม Header, Body และ Footer
 */
?>
<!-- wp:html -->
<div class="container my-4">
    <!-- Trigger Button -->
    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#demoModal">
        Open Popup Modal
    </button>

    <!-- Modal -->
    <div class="modal fade" id="demoModal" tabindex="-1" aria-labelledby="demoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="demoModalLabel">Modal Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This is a Bootstrap 5 Modal popup. You can put any content here — forms, images, text, or even other Bootstrap components.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /wp:html -->
