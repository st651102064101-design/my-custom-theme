<?php
/**
 * Title: Bootstrap Table (ตาราง)
 * Slug: my-custom-theme/bootstrap-table
 * Categories: my-special-design
 * Description: ตาราง Bootstrap สไตล์ striped พร้อม responsive บนมือถือ
 */
?>
<!-- wp:html -->
<div class="container my-5">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Product Name</th>
                    <th scope="col">Category</th>
                    <th scope="col">Price</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row">1</th>
                    <td>Power Inductor 10µH</td>
                    <td>Inductors</td>
                    <td>$2.50</td>
                    <td><span class="badge bg-success">In Stock</span></td>
                    <td><a href="#" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <tr>
                    <th scope="row">2</th>
                    <td>Flyback Transformer</td>
                    <td>Transformers</td>
                    <td>$15.00</td>
                    <td><span class="badge bg-success">In Stock</span></td>
                    <td><a href="#" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <tr>
                    <th scope="row">3</th>
                    <td>PCB Antenna 2.4GHz</td>
                    <td>Antennas</td>
                    <td>$8.75</td>
                    <td><span class="badge bg-warning text-dark">Low Stock</span></td>
                    <td><a href="#" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <tr>
                    <th scope="row">4</th>
                    <td>Toroidal Inductor 47µH</td>
                    <td>Inductors</td>
                    <td>$4.20</td>
                    <td><span class="badge bg-danger">Out of Stock</span></td>
                    <td><a href="#" class="btn btn-sm btn-outline-secondary disabled">View</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<!-- /wp:html -->
