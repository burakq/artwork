            </div>
        </section>
    </div>

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">© <?php echo date('Y'); ?> Artwork Authentication. Tüm hakları saklıdır.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Version: <?php 
                        $versionFile = __DIR__ . '/../../../../version.txt';
                        if (file_exists($versionFile)) {
                            echo file_get_contents($versionFile);
                        } else {
                            echo 'v1.0.5';
                        }
                    ?></p>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<?php if (isset($additional_js)) echo $additional_js; ?>
</body>
</html> 