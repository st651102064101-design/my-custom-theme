<?php
/**
 * Title: Bootstrap Pricing Table (ตารางราคา)
 * Slug: my-custom-theme/bootstrap-pricing
 * Categories: my-special-design
 * Description: ตารางเปรียบเทียบราคา 3 แพ็กเกจ พร้อมไฮไลท์แพ็กเกจแนะนำ
 */
?>
<!-- wp:html -->
<div class="container my-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Pricing Plans</h2>
        <p class="text-muted">Choose the plan that's right for you</p>
    </div>
    <div class="row g-4 justify-content-center">
        <!-- Basic -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm text-center">
                <div class="card-header bg-white py-4">
                    <h4 class="fw-bold mb-0">Basic</h4>
                </div>
                <div class="card-body d-flex flex-column py-4">
                    <div class="display-5 fw-bold text-primary mb-3">$19<small class="fs-6 text-muted fw-normal">/month</small></div>
                    <ul class="list-unstyled mb-4">
                        <li class="py-2 border-bottom">✓ 5 Products</li>
                        <li class="py-2 border-bottom">✓ Email Support</li>
                        <li class="py-2 border-bottom">✓ Basic Analytics</li>
                        <li class="py-2 border-bottom text-muted">✗ Priority Support</li>
                        <li class="py-2">✗ Custom Domain</li>
                    </ul>
                    <a href="#" class="btn btn-outline-primary mt-auto">Get Started</a>
                </div>
            </div>
        </div>
        <!-- Popular -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow border-primary text-center">
                <div class="card-header bg-primary text-white py-4">
                    <span class="badge bg-warning text-dark mb-2">Most Popular</span>
                    <h4 class="fw-bold mb-0 text-white">Professional</h4>
                </div>
                <div class="card-body d-flex flex-column py-4">
                    <div class="display-5 fw-bold text-primary mb-3">$49<small class="fs-6 text-muted fw-normal">/month</small></div>
                    <ul class="list-unstyled mb-4">
                        <li class="py-2 border-bottom">✓ 50 Products</li>
                        <li class="py-2 border-bottom">✓ Priority Support</li>
                        <li class="py-2 border-bottom">✓ Advanced Analytics</li>
                        <li class="py-2 border-bottom">✓ Custom Domain</li>
                        <li class="py-2">✗ API Access</li>
                    </ul>
                    <a href="#" class="btn btn-primary mt-auto">Get Started</a>
                </div>
            </div>
        </div>
        <!-- Enterprise -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm text-center">
                <div class="card-header bg-white py-4">
                    <h4 class="fw-bold mb-0">Enterprise</h4>
                </div>
                <div class="card-body d-flex flex-column py-4">
                    <div class="display-5 fw-bold text-primary mb-3">$99<small class="fs-6 text-muted fw-normal">/month</small></div>
                    <ul class="list-unstyled mb-4">
                        <li class="py-2 border-bottom">✓ Unlimited Products</li>
                        <li class="py-2 border-bottom">✓ 24/7 Support</li>
                        <li class="py-2 border-bottom">✓ Full Analytics</li>
                        <li class="py-2 border-bottom">✓ Custom Domain</li>
                        <li class="py-2">✓ API Access</li>
                    </ul>
                    <a href="#" class="btn btn-outline-primary mt-auto">Contact Sales</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /wp:html -->
