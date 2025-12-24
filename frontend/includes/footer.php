                </div><!-- /.main-content -->
            </div><!-- /.main-content-wrapper -->
        </div><!-- /.app-body -->
    </div><!-- /.app-layout -->
    
    <?php if (isset($extra_js)): foreach ($extra_js as $js): ?>
    <script src="<?php echo $js; ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>
