<?php
/**
 * Title: Bootstrap Form (แบบฟอร์ม)
 * Slug: my-custom-theme/bootstrap-form
 * Categories: my-special-design
 * Description: แบบฟอร์มติดต่อ Bootstrap พร้อม input, select, textarea และปุ่มส่ง
 */
?>
<!-- wp:html -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-4">Contact Us</h3>
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" placeholder="Enter first name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" placeholder="Enter last name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" placeholder="name@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" placeholder="0XXXXXXXXX">
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" id="subject">
                                    <option selected disabled>Choose a subject...</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="product">Product Question</option>
                                    <option value="support">Technical Support</option>
                                    <option value="quote">Request a Quote</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" placeholder="Your message here..." required></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to the terms and conditions
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /wp:html -->
