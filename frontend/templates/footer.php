<?php
/**
 * Layout Footer Component
 */
?>
    <script>
        // Pass PHP data to JavaScript
        window.currentUser = <?php echo json_encode(getCurrentUser()); ?>;
        window.currentPatient = <?php echo json_encode($patient ?? null); ?>;
        window.currentEncounter = <?php echo json_encode($encounter ?? null); ?>;
    </script>
</body>
</html>
