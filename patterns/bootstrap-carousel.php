<?php
/**
 * Title: Bootstrap Carousel (สไลด์โชว์)
 * Slug: my-custom-theme/bootstrap-carousel
 * Categories: my-special-design
 * Description: สไลด์โชว์รูปภาพ Bootstrap พร้อม indicators และ controls
 */
?>
<!-- wp:html -->
<div id="mainCarousel" class="carousel slide my-4" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner rounded-3 shadow">
        <div class="carousel-item active">
            <img src="https://placehold.co/1200x500/2563eb/ffffff?text=Slide+1+-+Your+Image+Here" class="d-block w-100" alt="Slide 1" style="object-fit:cover;height:500px;">
            <div class="carousel-caption d-none d-md-block">
                <h3>First Slide</h3>
                <p>Description for the first slide goes here.</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="https://placehold.co/1200x500/1e40af/ffffff?text=Slide+2+-+Your+Image+Here" class="d-block w-100" alt="Slide 2" style="object-fit:cover;height:500px;">
            <div class="carousel-caption d-none d-md-block">
                <h3>Second Slide</h3>
                <p>Description for the second slide goes here.</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="https://placehold.co/1200x500/3b82f6/ffffff?text=Slide+3+-+Your+Image+Here" class="d-block w-100" alt="Slide 3" style="object-fit:cover;height:500px;">
            <div class="carousel-caption d-none d-md-block">
                <h3>Third Slide</h3>
                <p>Description for the third slide goes here.</p>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
<!-- /wp:html -->
